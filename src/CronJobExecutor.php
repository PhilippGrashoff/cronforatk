<?php

declare(strict_types=1);

namespace cronforatk;

use Atk4\Data\Exception;
use Atk4\Data\Persistence;
use DateTime;
use Throwable;


class CronJobExecutor
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
     * @throws Exception
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
        foreach (CronJobModel::$intervalSettings as $interval => $intervalName) {
            $cronJobModels = new CronJobModel($this->persistence);
            $cronJobModels->addCondition('interval', $interval);
            $cronJobModels->addCondition('is_active', 1);

            foreach ($cronJobModels as $cronJobEntity) {
                $this->executeCronIfScheduleMatches($cronJobEntity);
            }
        }
    }

    /**
     * @param CronJobModel $cronJobEntity
     * @return void
     * @throws \Atk4\Core\Exception
     * @throws Exception
     */
    protected function executeCronIfScheduleMatches(CronJobModel $cronJobEntity): void
    {
        $entityInterval = $cronJobEntity->get('interval');
        if ($entityInterval === 'YEARLY' && $this->checkYearlyExecutionIsNow($cronJobEntity)) {
            $this->executeCronJob($cronJobEntity);
        } elseif ($entityInterval === 'MONTHLY' && $this->checkMonthlyExecutionIsNow($cronJobEntity)) {
            $this->executeCronJob($cronJobEntity);
        } elseif ($entityInterval === 'WEEKLY' && $this->checkWeeklyExecutionIsNow($cronJobEntity)) {
            $this->executeCronJob($cronJobEntity);
        } elseif ($entityInterval === 'DAILY' && $this->checkDailyExecutionIsNow($cronJobEntity)) {
            $this->executeCronJob($cronJobEntity);
        } elseif ($entityInterval === 'HOURLY' && $this->checkHourlyExecutionIsNow($cronJobEntity)) {
            $this->executeCronJob($cronJobEntity);
        } elseif ($entityInterval === 'MINUTELY' && $this->checkMinutelyExecutionIsNow($cronJobEntity)) {
            $this->executeCronJob($cronJobEntity);
        }
    }

    /**
     * @param CronJobModel $cronJobEntity
     * @return bool
     */
    protected function checkYearlyExecutionIsNow(CronJobModel $cronJobEntity): bool
    {
        if (
            !$cronJobEntity->get('date_yearly') instanceof \DateTimeInterface
            || !$cronJobEntity->get('time_yearly') instanceof \DateTimeInterface
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
     * @param CronJobModel $cronJobEntity
     * @return bool
     */
    protected function checkMonthlyExecutionIsNow(CronJobModel $cronJobEntity): bool
    {
        if (
            $cronJobEntity->get('day_monthly') < 1
            || $cronJobEntity->get('day_monthly') > 28 //TODO this is simply wrong
            || !$cronJobEntity->get('time_monthly') instanceof \DateTimeInterface
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
     * @param CronJobModel $cronJobEntity
     * @return bool
     */
    protected function checkWeeklyExecutionIsNow(CronJobModel $cronJobEntity): bool
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
     * @param CronJobModel $cronJobEntity
     * @return bool
     */
    protected function checkDailyExecutionIsNow(CronJobModel $cronJobEntity): bool
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
     * @param CronJobModel $cronJobEntity
     * @return bool
     */
    protected function checkHourlyExecutionIsNow(CronJobModel $cronJobEntity): bool
    {
        return $this->currentMinute === $cronJobEntity->get('minute_hourly');
    }

    /**
     * @param CronJobModel $cronJobEntity
     * @return bool
     */
    protected function checkMinutelyExecutionIsNow(CronJobModel $cronJobEntity): bool
    {
        if ($cronJobEntity->get('interval_minutely') == 'EVERY_MINUTE') {
            return true;
        } elseif (
            $cronJobEntity->get('interval_minutely') == 'EVERY_FIFTH_MINUTE'
            && ($this->currentMinute % 5) === $cronJobEntity->get('offset_minutely')
        ) {
            return true;
        } elseif (
            $cronJobEntity->get('interval_minutely') == 'EVERY_FIFTEENTH_MINUTE'
            && ($this->currentMinute % 15) === $cronJobEntity->get('offset_minutely')
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param CronJobModel $cronJobEntity
     * @return void
     * @throws \Atk4\Core\Exception
     * @throws Exception
     */
    protected function executeCronJob(CronJobModel $cronJobEntity): void
    {
        $cronJobExecutionLog = (new CronJobExecutionLog($this->persistence))->createEntity();
        $cronJobExecutionLog->set('cronjob_id', $cronJobEntity->getId());
        $cronJobExecutionLog->set('execution_datetime', new DateTime());
        $startOfCron = microtime(true);

        try {
            $cronJobClass = $cronJobEntity->get('cronjob_class');
            $cronJobInstance = new $cronJobClass($this->persistence, $cronJobEntity->get('defaults'));
            $cronJobInstance->execute();
            $cronJobExecutionLog->set('execution_successful', true);
            $cronJobExecutionLog->set('execution_output', $cronJobInstance->getLastExecutionLog());
            $this->reportSuccess($cronJobExecutionLog);
        } //catch any errors as more than one cron could be executed per minutely run
        catch (Throwable $e) {
            $cronJobExecutionLog->set('execution_successful', false);
            $cronJobExecutionLog->set('execution_output', [$e->getMessage()]);
            $this->reportFailure($cronJobExecutionLog, $e);
        }
        $cronJobExecutionLog->set('execution_duration', microtime(true) - $startOfCron);
        $cronJobExecutionLog->save();
    }

    /**
     * This function can be implemented in child classes which extend this class in order you want some custom reporting
     * (e.g. an Email), per successful cronjob run
     *
     * @param CronJobExecutionLog $cronJobExecutionLog
     * @return void
     */
    protected function reportSuccess(CronJobExecutionLog $cronJobExecutionLog): void
    {
    }

    /**
     * This function can be implemented in child classes which extend this class in order you want some custom reporting
     * (e.g. an Email), per successful cronjob run
     *
     * @param CronJobExecutionLog $cronJobExecutionLog
     * @param Throwable $e
     * @return void
     */
    protected function reportFailure(CronJobExecutionLog $cronJobExecutionLog, Throwable $e): void
    {
    }
}