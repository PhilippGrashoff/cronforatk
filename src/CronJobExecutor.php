<?php

declare(strict_types=1);

namespace cronforatk;

use Atk4\Data\Persistence;
use DateTime;


class CronJobExecutor
{
    protected Persistence $persistence;

    public array $intervalSettings = [
        'YEARLY' => 'Jährlich',
        'MONTHLY' => 'Monatlich',
        'WEEKLY' => 'Wöchentlich',
        'DAILY' => 'Täglich',
        'HOURLY' => 'Stündlich',
        'MINUTELY' => 'Minütlich'
    ];

    public array $minutelyIntervalSettings = [
        'EVERY_MINUTE' => 'Jede Minute',
        'EVERY_FIFTH_MINUTE' => 'Alle 5 Minuten',
        'EVERY_FIFTEENTH_MINUTE' => 'Alle 15 Minuten'
    ];

    public string $currentDate;
    public int $currentWeekday;
    public int $currentDay;
    public string $currentTime;
    public int $currentMinute;


    public function __construct(Persistence $persistence)
    {
        $this->persistence = $persistence;
    }

    /**
     * @param DateTime|null $dateTime
     * @return void
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
        foreach ($this->intervalSettings as $interval => $type) {
            $cronJobModels = new CronJobModel($this->persistence);
            $cronJobModels->addCondition('interval', $interval);
            $cronJobModels->addCondition('is_active', 1);

            foreach ($cronJobModels as $cronJobEntity) {
                $this->executeCronIfScheduleMatches($cronJobEntity);
            }
        }
    }

    private function executeCronIfScheduleMatches(CronJobModel $cronJobEntity): void
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

    private function checkYearlyExecutionIsNow(CronJobModel $cronJobEntity): bool
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


    private function checkMonthlyExecutionIsNow(CronJobModel $cronJobEntity): bool
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

    private function checkWeeklyExecutionIsNow(CronJobModel $cronJobEntity): bool
    {
        if (
            $cronJobEntity->get('weekday_weekly') !== $this->currentWeekday
            || $this->currentTime !== $cronJobEntity->get('time_weekly')->format('H:i')
        ) {
            return false;
        }

        return true;
    }

    private function checkDailyExecutionIsNow(CronJobModel $cronJobEntity): bool
    {
        if (
            !$cronJobEntity->get('time_daily')
            || $this->currentTime !== $cronJobEntity->get('time_daily')->format('H:i')
        ) {
            return false;
        }
        return true;
    }

    private function checkHourlyExecutionIsNow(CronJobModel $cronJobEntity): bool
    {
        return $this->currentMinute !== $cronJobEntity->get('minute_hourly');
    }

    private function checkMinutelyExecutionIsNow(CronJobModel $cronJobEntity): bool
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

    //TODO: Make protected? 
    private function executeCronJob(CronJobModel $cronJobEntity): bool
    {
        try {
            $cronJobEntity->set('last_executed', new DateTime());
            $cronJobEntity->save();

            $startOfCron = microtime(true);

            $cronJobEntity->execute();
            $cronJobEntity->set('last_execution_duration', microtime(true) - $startOfCron);
            $cronJobEntity->set('last_execution_success', true);
            $cronJobEntity->set('last_execution_output', $cronJobEntity->getLastExecutionLog());
            $cronJobEntity->save();

            $cronJobEntity->reportSuccess($cronJobEntity);

            return true;
        } //catch any errors as more than one cron could be executed per minutely run
        catch (\Throwable $e) {
            $cronJobEntity->set('last_execution_success', false);
            $cronJobEntity->set('last_execution_output', [$e->getMessage()]);
            $cronJobEntity->save();

            $this->reportFailure($e);
            return false;
        }
    }

    protected function reportSuccess(BaseCronJob $cronJob)
    {
    }

    protected function reportFailure(\Throwable $e)
    {
    }
}