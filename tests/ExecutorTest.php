<?php

declare(strict_types=1);

namespace PhilippR\Atk4\Cron\Tests;

use Atk4\Data\Persistence;
use Atk4\Data\Persistence\Sql;
use Atk4\Data\Schema\TestCase;
use DateTime;
use PhilippR\Atk4\Cron\ExecutionLog;
use PhilippR\Atk4\Cron\Executor;
use PhilippR\Atk4\Cron\Scheduler;
use PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJob;
use PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJobWithExceptionInExecute;

class ExecutorTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new Sql('sqlite::memory:');
        $this->createMigrator(new Scheduler($this->db))->create();
        $this->createMigrator(new ExecutionLog($this->db))->create();
    }

    public function testRunYearly(): void
    {
        $testTime = new DateTime('2020-11-05');
        $testTime->setTime(3, 11);
        //this one should be executed
        $scheduler1 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_YEARLY,
                'date_yearly' => $testTime,
                'time_yearly' => $testTime
            ]
        );
        $scheduler2 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_YEARLY,
                'date_yearly' => $testTime,
                'time_yearly' => (clone $testTime)->modify('-1 Minute'),
            ]
        );
        $scheduler3 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_YEARLY,
                'date_yearly' => $testTime,
                'time_yearly' => (clone $testTime)->modify('+1 Minute'),
            ]
        );
        $scheduler4 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_YEARLY,
                'date_yearly' => (clone $testTime)->modify('+1 Day'),
                'time_yearly' => $testTime,
            ]
        );

        //only one should be executed
        $executor = new Executor($this->db);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();
        $scheduler3->reload();
        $scheduler4->reload();


        self::assertInstanceOf(ExecutionLog::class, self::getLastExecutionLog($scheduler1));
        self::assertNull(self::getLastExecutionLog($scheduler2));
        self::assertNull(self::getLastExecutionLog($scheduler3));
        self::assertNull(self::getLastExecutionLog($scheduler4));
    }

    public function testSkipYearlyIfNoDateYearlySet(): void
    {
        $testTime = new DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $scheduler1 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_YEARLY,
                'time_yearly' => $testTime,
            ]
        );

        $executor = new Executor($this->db);
        $executor->run($testTime);

        $scheduler1->reload();
        self::assertNull(self::getLastExecutionLog($scheduler1));
    }

    public function testRunMonthly(): void
    {
        $testTime = new DateTime('2020-08-05');
        $testTime->setTime(3, 14);
        //this one should be executed
        $scheduler1 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_MONTHLY,
                'day_monthly' => (int)$testTime->format('d'),
                'time_monthly' => $testTime,
            ]
        );
        $scheduler2 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_MONTHLY,
                'day_monthly' => (int)$testTime->format('d'),
                'time_monthly' => (clone $testTime)->modify('-1 Minute'),
            ]
        );
        $scheduler3 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_MONTHLY,
                'day_monthly' => (int)$testTime->format('d'),
                'time_monthly' => (clone $testTime)->modify('+1 Minute'),
            ]
        );
        $scheduler4 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_MONTHLY,
                'day_monthly' => (int)(clone $testTime)->modify('+1 Day')->format('d'),
                'time_monthly' => $testTime
            ]
        );
        $scheduler5 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_MONTHLY,
                'day_monthly' => (int)(clone $testTime)->modify('+1 Day')->format('d'),
                'time_monthly' => $testTime,
            ]
        );

        //only one should be executed
        $executor = new Executor($this->db);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();
        $scheduler3->reload();
        $scheduler4->reload();
        $scheduler5->reload();

        self::assertInstanceOf(ExecutionLog::class, self::getLastExecutionLog($scheduler1));
        self::assertNull(self::getLastExecutionLog($scheduler2));
        self::assertNull(self::getLastExecutionLog($scheduler3));
        self::assertNull(self::getLastExecutionLog($scheduler4));
        self::assertNull(self::getLastExecutionLog($scheduler5));
    }

    public function testSkipMonthlyIfNoTimeSet(): void
    {
        $testTime = new DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $scheduler1 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_MONTHLY,
                'day_monthly' => 5,
            ]
        );

        $executor = new Executor($this->db);
        $executor->run($testTime);

        $scheduler1->reload();
        self::assertNull(self::getLastExecutionLog($scheduler1));
    }

    public function testRunWeekly(): void
    {
        $testTime = new DateTime('2020-06-03');
        $testTime->setTime(4, 34);
        //this one should be executed
        $scheduler1 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_WEEKLY,
                'weekday_weekly' => (int)$testTime->format('N'),
                'time_weekly' => $testTime,
            ]
        );
        $scheduler2 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_WEEKLY,
                'weekday_weekly' => (int)$testTime->format('N'),
                'time_weekly' => (clone $testTime)->modify('-1 Minute'),
            ]
        );
        $scheduler3 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_WEEKLY,
                'weekday_weekly' => (int)$testTime->format('N'),
                'time_weekly' => (clone $testTime)->modify('+1 Minute'),
            ]
        );
        $scheduler4 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_WEEKLY,
                'weekday_weekly' => (int)(clone $testTime)->modify('-1 Day')->format('N'),
                'time_weekly' => $testTime,
            ]
        );
        $scheduler5 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_WEEKLY,
                'weekday_weekly' => (int)(clone $testTime)->modify('+1 Day')->format('N'),
                'time_weekly' => $testTime,
            ]
        );

        //only one should be executed
        $executor = new Executor($this->db);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();
        $scheduler3->reload();
        $scheduler4->reload();
        $scheduler5->reload();

        self::assertInstanceOf(ExecutionLog::class, self::getLastExecutionLog($scheduler1));
        self::assertNull(self::getLastExecutionLog($scheduler2));
        self::assertNull(self::getLastExecutionLog($scheduler3));
        self::assertNull(self::getLastExecutionLog($scheduler4));
        self::assertNull(self::getLastExecutionLog($scheduler5));
    }

    public function testRunDaily(): void
    {
        $testTime = new DateTime();
        $testTime->setTime(15, 3);

        //this one should be executed
        $scheduler1 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_DAILY,
                'time_daily' => $testTime,
            ]
        );
        $scheduler2 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_DAILY,
                'time_daily' => (clone $testTime)->modify('-1 Minute')
            ]
        );
        $scheduler3 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_DAILY,
                'time_daily' => (clone $testTime)->modify('+1 Minute')
            ]
        );

        //only one should be executed
        $executor = new Executor($this->db);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();
        $scheduler3->reload();

        self::assertInstanceOf(ExecutionLog::class, self::getLastExecutionLog($scheduler1));
        self::assertNull(self::getLastExecutionLog($scheduler2));
        self::assertNull(self::getLastExecutionLog($scheduler3));
    }

    public function testRunHourly(): void
    {
        $testTime = new DateTime();
        $testTime->setTime(14, 35);
        //this one should be executed
        $scheduler1 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_HOURLY,
                'minute_hourly' => (int)$testTime->format('i')
            ]
        );
        $scheduler2 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_HOURLY,
                'minute_hourly' => (int)(clone $testTime)->modify('+1 Minute')->format('i')
            ]
        );
        $scheduler3 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_HOURLY,
                'minute_hourly' => (int)(clone $testTime)->modify('-1 Minute')->format('i')
            ]
        );

        $executor = new Executor($this->db);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();
        $scheduler3->reload();

        self::assertInstanceOf(ExecutionLog::class, self::getLastExecutionLog($scheduler1));
        self::assertNull(self::getLastExecutionLog($scheduler2));
        self::assertNull(self::getLastExecutionLog($scheduler3));
    }

    public function testRunMinutely(): void
    {
        $testTime = new DateTime();
        $testTime->setTime(3, 16);
        //this one should be executed
        $scheduler1 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_MINUTELY,
                'interval_minutely' => Scheduler::MINUTELY_INTERVAL_EVERY_MINUTE,
            ]
        );
        $scheduler2 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_MINUTELY,
                'interval_minutely' => 'EVERY_FIFTH_MINUTE',
            ]
        );
        $scheduler3 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_MINUTELY,
                'interval_minutely' => 'EVERY_FIFTEENTH_MINUTE',
            ]
        );

        $executor = new Executor($this->db);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();
        $scheduler3->reload();

        self::assertInstanceOf(ExecutionLog::class, self::getLastExecutionLog($scheduler1));
        self::assertNull(self::getLastExecutionLog($scheduler2));
        self::assertNull(self::getLastExecutionLog($scheduler3));
    }

    public function testRunMinutelyOffset(): void
    {
        $testTime = new DateTime();
        $testTime->setTime(3, 18);

        //this one should be executed
        $scheduler1 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_MINUTELY,
                'interval_minutely' => 'EVERY_FIFTH_MINUTE',
                'offset_minutely' => 3,
            ]
        );
        //this one should be executed, too
        $scheduler2 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_MINUTELY,
                'interval_minutely' => 'EVERY_FIFTEENTH_MINUTE',
                'offset_minutely' => 3,
            ]
        );
        $scheduler3 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_MINUTELY,
                'interval_minutely' => 'EVERY_FIFTH_MINUTE',
            ]
        );
        $scheduler4 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_MINUTELY,
                'interval_minutely' => 'EVERY_FIFTEENTH_MINUTE',
            ]
        );

        $executor = new Executor($this->db);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();
        $scheduler3->reload();
        $scheduler4->reload();

        self::assertInstanceOf(ExecutionLog::class, self::getLastExecutionLog($scheduler1));
        self::assertInstanceOf(ExecutionLog::class, self::getLastExecutionLog($scheduler2));
        self::assertNull(self::getLastExecutionLog($scheduler3));
        self::assertNull(self::getLastExecutionLog($scheduler4));
    }

    public function testNonActiveCronInRun(): void
    {
        $testTime = new DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $scheduler1 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_MONTHLY,
                'day_monthly' => 5,
                'time_monthly' => $testTime,
            ]
        );

        $scheduler1->set('is_active', 0);
        $scheduler1->save();
        $executor = new Executor($this->db);
        $executor->run($testTime);

        $scheduler1->reload();
        self::assertNull(self::getLastExecutionLog($scheduler1));

        $scheduler1->set('is_active', 1);
        $scheduler1->save();
        $executor = new Executor($this->db);
        $executor->run($testTime);

        $scheduler1->reload();
        self::assertInstanceOf(ExecutionLog::class, self::getLastExecutionLog($scheduler1));
    }

    public function testExceptionInExecuteDoesNotStopExecutionOfOthers(): void
    {
        $testTime = new DateTime('2020-05-05');
        $testTime->setTime(3, 3);

        $scheduler1 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_YEARLY,
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );
        $scheduler1->set('cronjob_class', SomeCronJobWithExceptionInExecute::class);
        $scheduler1->save();

        $scheduler2 = self::getScheduler(
            $this->db,
            [
                'interval' => Scheduler::INTERVAL_DAILY,
                'time_daily' => $testTime,
            ]
        );

        $executor = new Executor($this->db);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();

        self::assertInstanceOf(ExecutionLog::class, self::getLastExecutionLog($scheduler2));
        self::assertInstanceOf(ExecutionLog::class, self::getLastExecutionLog($scheduler1));
    }

    public static function getScheduler(Persistence $persistence, array $set = []): Scheduler
    {
        $scheduler = (new Scheduler($persistence))->createEntity();

        $scheduler->set('cronjob_class', SomeCronJob::class);
        $scheduler->set('is_active', 1);
        $scheduler->setMulti($set);

        $scheduler->save();

        return $scheduler;
    }

    public
    static function getLastExecutionLog(
        Scheduler $scheduler
    ): ?ExecutionLog {
        $executionLog = $scheduler->ref(ExecutionLog::class);
        return $executionLog->tryLoadAny();
    }
}