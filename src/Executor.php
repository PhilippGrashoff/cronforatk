<?php declare(strict_types=1);

namespace PhilippR\Atk4\Cron;

use Atk4\Data\Exception;
use Atk4\Data\Persistence;
use DateTime;
use DateTimeInterface;
use Throwable;


class Executor
{
    protected Persistence $persistence;

    protected string $currentDate;
    protected int $currentWeekday;
    protected int $currentDay;
    protected string $currentTime;
    protected int $currentMinute;


    public function __construct(Persistence $persistence)
    {
        $this->persistence = $persistence;
    }

    /**
     * @param DateTime|null $dateTime
     * @return void
     * @throws \Atk4\Core\Exception
     * @throws Exception|Throwable
     */
    public function run(DateTime $dateTime = null): void
    {
        //for testing, a dateTime object can be provided. In Normal operation, don't pass anything to use current time
        if ($dateTime === null) {
            $dateTime = new DateTime();
        }

        $this->currentDate = $dateTime->format('m-d');
        $this->currentWeekday = (int)$dateTime->format('N');
        $this->currentDay = (int)$dateTime->format('d');
        $this->currentTime = $dateTime->format('H:i');
        $this->currentMinute = (int)$dateTime->format('i');

        //execute yearly first, minutely last.
        $this->runOrderedByInterval();
    }

    /**
     * This general implementation runs yearly cronjobs first, minutely last. It is advisable to overwrite this method
     * as it creates a new Model and a new DB request per interval (yearly, monthly, weekly and so on) but can be
     * handled by a single db request if cronjobs are ordered by interval.
     * A Mysql/Mariadb implementation would look like this:
     *
     * protected function runOrderedByInterval(): void
     * {
     *     $cronJobModel = new Scheduler($this->persistence);
     *     $cronJobModel->addCondition('is_active', 1);
     *     $cronJobModel->setOrder('ORDER BY FIELD(interval,' . implode(',', array_keys(Scheduler::getIntervals())) . ')');
     *     foreach ($cronJobModel as $cronJobEntity) {
     *         $this->executeCronIfScheduleMatches($cronJobEntity);
     *     }
     * }
     *
     * @return void
     * @throws Exception
     * @throws Throwable
     * @throws \Atk4\Core\Exception
     */
    protected function runOrderedByInterval(): void
    {
        foreach (Scheduler::getIntervals() as $interval => $intervalName) {
            $cronJobModel = new Scheduler($this->persistence);
            $cronJobModel->addCondition('interval', $interval);
            $cronJobModel->addCondition('is_active', 1);

            foreach ($cronJobModel as $cronJobEntity) {
                $this->executeCronIfScheduleMatches($cronJobEntity);
            }
        }
    }


    /**
     * @param Scheduler $cronJobEntity
     * @return void
     * @throws \Atk4\Core\Exception
     * @throws Exception|Throwable
     */
    protected function executeCronIfScheduleMatches(Scheduler $cronJobEntity): void
    {
        $entityInterval = $cronJobEntity->get('interval');
        if ($entityInterval === Scheduler::INTERVAL_YEARLY && $this->checkYearlyExecutionIsNow($cronJobEntity)) {
            $this->executeCronJob($cronJobEntity);
        } elseif ($entityInterval === Scheduler::INTERVAL_MONTHLY && $this->checkMonthlyExecutionIsNow($cronJobEntity)) {
            $this->executeCronJob($cronJobEntity);
        } elseif ($entityInterval === Scheduler::INTERVAL_WEEKLY && $this->checkWeeklyExecutionIsNow($cronJobEntity)) {
            $this->executeCronJob($cronJobEntity);
        } elseif ($entityInterval === Scheduler::INTERVAL_DAILY && $this->checkDailyExecutionIsNow($cronJobEntity)) {
            $this->executeCronJob($cronJobEntity);
        } elseif ($entityInterval === Scheduler::INTERVAL_HOURLY && $this->checkHourlyExecutionIsNow($cronJobEntity)) {
            $this->executeCronJob($cronJobEntity);
        } elseif ($entityInterval === Scheduler::INTERVAL_MINUTELY && $this->checkMinutelyExecutionIsNow($cronJobEntity)) {
            $this->executeCronJob($cronJobEntity);
        }
    }

    /**
     * @param Scheduler $cronJobEntity
     * @return bool
     */
    protected function checkYearlyExecutionIsNow(Scheduler $cronJobEntity): bool
    {
        if (
            !$cronJobEntity->get('date_yearly') instanceof DateTimeInterface
            || !$cronJobEntity->get('time_yearly') instanceof DateTimeInterface
        ) {
            return false;
        }

        if (
            $this->currentDate !== $cronJobEntity->get('date_yearly')->format('m-d')
            || $this->currentTime !== $cronJobEntity->get('time_yearly')->format('H:i')
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param Scheduler $cronJobEntity
     * @return bool
     */
    protected function checkMonthlyExecutionIsNow(Scheduler $cronJobEntity): bool
    {
        if (
            $cronJobEntity->get('day_monthly') < 1
            || $cronJobEntity->get('day_monthly') > 28 //TODO this is simply wrong
            || !$cronJobEntity->get('time_monthly') instanceof DateTimeInterface
        ) {
            return false;
        }
        if (
            $this->currentDay !== $cronJobEntity->get('day_monthly')
            || $this->currentTime !== $cronJobEntity->get('time_monthly')->format('H:i')) {
            return false;
        }

        return true;
    }

    /**
     * @param Scheduler $cronJobEntity
     * @return bool
     */
    protected function checkWeeklyExecutionIsNow(Scheduler $cronJobEntity): bool
    {
        if (
            $cronJobEntity->get('weekday_weekly') !== $this->currentWeekday
            || $this->currentTime !== $cronJobEntity->get('time_weekly')->format('H:i')
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param Scheduler $cronJobEntity
     * @return bool
     */
    protected function checkDailyExecutionIsNow(Scheduler $cronJobEntity): bool
    {
        if (
            !$cronJobEntity->get('time_daily')
            || $this->currentTime !== $cronJobEntity->get('time_daily')->format('H:i')
        ) {
            return false;
        }
        return true;
    }

    /**
     * @param Scheduler $cronJobEntity
     * @return bool
     */
    protected function checkHourlyExecutionIsNow(Scheduler $cronJobEntity): bool
    {
        return $this->currentMinute === $cronJobEntity->get('minute_hourly');
    }

    /**
     * @param Scheduler $cronJobEntity
     * @return bool
     */
    protected function checkMinutelyExecutionIsNow(Scheduler $cronJobEntity): bool
    {
        if ($cronJobEntity->get('interval_minutely') == Scheduler::MINUTELY_INTERVAL_EVERY_MINUTE) {
            return true;
        } elseif (
            $cronJobEntity->get('interval_minutely') == Scheduler::MINUTELY_INTERVAL_EVERY_FIFTH_MINUTE
            && ($this->currentMinute % 5) === $cronJobEntity->get('offset_minutely')
        ) {
            return true;
        } elseif (
            $cronJobEntity->get('interval_minutely') == Scheduler::MINUTELY_INTERVAL_EVERY_FIFTEENTH_MINUTE
            && ($this->currentMinute % 15) === $cronJobEntity->get('offset_minutely')
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param Scheduler $entity
     * @return void
     * @throws \Atk4\Core\Exception
     * @throws Exception|Throwable
     */
    protected function executeCronJob(Scheduler $entity): void
    {
        $executionLog = (new ExecutionLog($this->persistence))->createEntity();
        $executionLog->set('scheduler_id', $entity->getId());
        $executionLog->set('execution_datetime', new DateTime());
        $startOfCron = microtime(true);
        try {
            /** @var class-string<BaseCronJob> $cronJobClass */
            $cronJobClass = $entity->get('cronjob_class');
            $cronJobInstance = new $cronJobClass($this->persistence, $entity->get('defaults') ?? []);
            $cronJobInstance->execute();
            $executionLog->set('execution_successful', true);
            $executionLog->set('execution_output', $cronJobInstance->getExecutionLog());
            $this->reportSuccess($executionLog);
        } //catch any errors as more than one cron could be executed per minutely run
        catch (Throwable $e) {
            $executionLog->set('execution_successful', false);
            $executionLog->set('execution_output', [$e->getMessage()]);
            $this->reportFailure($executionLog, $e);
        }
        $executionLog->set('execution_duration', microtime(true) - $startOfCron);
        if (
            $entity->get('logging') === Scheduler::LOGGING_ALWAYS_LOG
            || (
                $entity->get('logging') === Scheduler::LOGGING_ONLY_LOG_IF_OUTPUT
                && count($executionLog->get('execution_output')) > 0
            )
        ) {
            $executionLog->save();
        }
    }

    /**
     * This function can be implemented in child classes which extend this class in order you want some custom reporting
     * (e.g., an Email, passing to central logger), per successful cronjob run
     *
     * @param ExecutionLog $cronJobExecutionLog
     * @return void
     */
    protected function reportSuccess(ExecutionLog $cronJobExecutionLog): void
    {
    }

    /**
     * This function can be implemented in child classes which extend this class in order you want some custom reporting
     * (e.g., an Email, passing to central logger), per successful cronjob run
     *
     * @param ExecutionLog $cronJobExecutionLog
     * @param Throwable $e
     * @return void
     */
    protected function reportFailure(ExecutionLog $cronJobExecutionLog, Throwable $e): void
    {
    }
}