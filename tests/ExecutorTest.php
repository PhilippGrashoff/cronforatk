<?php

declare(strict_types=1);

namespace cronforatk\tests;

use Atk4\Data\Persistence;
use atkextendedtestcase\TestCase;
use cronforatk\ExecutionLog;
use cronforatk\Executor;
use cronforatk\Scheduler;
use cronforatk\tests\testclasses\SomeCronJob;
use cronforatk\tests\testclasses\SomeCronJobWithExceptionInExecute;

class ExecutorTest extends TestCase
{

    protected array $sqlitePersistenceModels = [
        Scheduler::class,
        ExecutionLog::class
    ];


    public function testRunYearly(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-11-05');
        $testTime->setTime(3, 11);
        //this one should be executed
        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime
            ]
        );
        $scheduler2 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => (clone $testTime)->modify('-1 Minute'),
            ]
        );
        $scheduler3 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => (clone $testTime)->modify('+1 Minute'),
            ]
        );
        $scheduler4 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => (clone $testTime)->modify('+1 Day'),
                'time_yearly' => $testTime,
            ]
        );

        //only one should be executed
        $executor = new Executor($persistence);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();
        $scheduler3->reload();
        $scheduler4->reload();

        self::assertInstanceOf(\Datetime::class, $scheduler1->get('last_executed'));
        self::assertNull($scheduler2->get('last_executed'));
        self::assertNull($scheduler3->get('last_executed'));
        self::assertNull($scheduler4->get('last_executed'));
    }

    public function testSkipYearlyIfNoDateYearlySet()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'YEARLY',
                'time_yearly' => $testTime,
            ]
        );

        $executor = new Executor($persistence);
        $executor->run($testTime);

        $scheduler1->reload();
        self::assertNull($scheduler1->get('last_executed'));
    }

    public function testRunMonthly(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-08-05');
        $testTime->setTime(3, 14);
        //this one should be executed
        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => (int)$testTime->format('d'),
                'time_monthly' => $testTime,
            ]
        );
        $scheduler2 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => (int)$testTime->format('d'),
                'time_monthly' => (clone $testTime)->modify('-1 Minute'),
            ]
        );
        $scheduler3 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => (int)$testTime->format('d'),
                'time_monthly' => (clone $testTime)->modify('+1 Minute'),
            ]
        );
        $scheduler4 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => (int)(clone $testTime)->modify('+1 Day')->format('d'),
                'time_monthly' => $testTime
            ]
        );
        $scheduler5 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => (int)(clone $testTime)->modify('+1 Day')->format('d'),
                'time_monthly' => $testTime,
            ]
        );

        //only one should be executed
        $executor = new Executor($persistence);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();
        $scheduler3->reload();
        $scheduler4->reload();
        $scheduler5->reload();

        self::assertInstanceOf(\Datetime::class, $scheduler1->get('last_executed'));
        self::assertNull($scheduler2->get('last_executed'));
        self::assertNull($scheduler3->get('last_executed'));
        self::assertNull($scheduler4->get('last_executed'));
        self::assertNull($scheduler5->get('last_executed'));
    }

    public function testSkipMonthlyIfNoTimeSet()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 5,
            ]
        );

        $executor = new Executor($persistence);
        $executor->run($testTime);

        $scheduler1->reload();
        self::assertNull($scheduler1->get('last_executed'));
    }

    public function testRunWeekly(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-06-03');
        $testTime->setTime(4, 34);
        //this one should be executed
        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => (int)$testTime->format('N'),
                'time_weekly' => $testTime,
            ]
        );
        $scheduler2 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => (int)$testTime->format('N'),
                'time_weekly' => (clone $testTime)->modify('-1 Minute'),
            ]
        );
        $scheduler3 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => (int)$testTime->format('N'),
                'time_weekly' => (clone $testTime)->modify('+1 Minute'),
            ]
        );
        $scheduler4 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => (int)(clone $testTime)->modify('-1 Day')->format('N'),
                'time_weekly' => $testTime,
            ]
        );
        $scheduler5 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => (int)(clone $testTime)->modify('+1 Day')->format('N'),
                'time_weekly' => $testTime,
            ]
        );

        //only one should be executed
        $executor = new Executor($persistence);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();
        $scheduler3->reload();
        $scheduler4->reload();
        $scheduler5->reload();

        self::assertInstanceOf(\Datetime::class, $scheduler1->get('last_executed'));
        self::assertNull($scheduler2->get('last_executed'));
        self::assertNull($scheduler3->get('last_executed'));
        self::assertNull($scheduler4->get('last_executed'));
        self::assertNull($scheduler5->get('last_executed'));
    }

    public function testRunDaily(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime();
        $testTime->setTime(15, 3);

        //this one should be executed
        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'DAILY',
                'time_daily' => $testTime,
            ]
        );
        $scheduler2 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'DAILY',
                'time_daily' => (clone $testTime)->modify('-1 Minute')
            ]
        );
        $scheduler3 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'DAILY',
                'time_daily' => (clone $testTime)->modify('+1 Minute')
            ]
        );

        //only one should be executed
        $executor = new Executor($persistence);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();
        $scheduler3->reload();

        self::assertInstanceOf(\Datetime::class, $scheduler1->get('last_executed'));
        self::assertNull($scheduler2->get('last_executed'));
        self::assertNull($scheduler3->get('last_executed'));
    }

    public function testRunHourly()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime();
        $testTime->setTime(14, 35);
        //this one should be executed
        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'HOURLY',
                'minute_hourly' => (int)$testTime->format('i')
            ]
        );
        $scheduler2 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'HOURLY',
                'minute_hourly' => (int)(clone $testTime)->modify('+1 Minute')->format('i')
            ]
        );
        $scheduler3 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'HOURLY',
                'minute_hourly' => (int)(clone $testTime)->modify('-1 Minute')->format('i')
            ]
        );

        $executor = new Executor($persistence);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();
        $scheduler3->reload();

        self::assertInstanceOf(\Datetime::class, $scheduler1->get('last_executed'));
        self::assertNull($scheduler2->get('last_executed'));
        self::assertNull($scheduler3->get('last_executed'));
    }

    public function testRunMinutely()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime();
        $testTime->setTime(3, 16);
        //this one should be executed
        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_MINUTE',
            ]
        );
        $scheduler2 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTH_MINUTE',
            ]
        );
        $scheduler3 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTEENTH_MINUTE',
            ]
        );

        $executor = new Executor($persistence);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();
        $scheduler3->reload();

        self::assertInstanceOf(\Datetime::class, $scheduler1->get('last_executed'));
        self::assertNull($scheduler2->get('last_executed'));
        self::assertNull($scheduler3->get('last_executed'));
    }

    public function testRunMinutelyOffset()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime();
        $testTime->setTime(3, 18);

        //this one should be executed
        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTH_MINUTE',
                'offset_minutely' => 3,
            ]
        );
        //this one should be executed, too
        $scheduler2 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTEENTH_MINUTE',
                'offset_minutely' => 3,
            ]
        );
        $scheduler3 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTH_MINUTE',
            ]
        );
        $scheduler4 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTEENTH_MINUTE',
            ]
        );

        $executor = new Executor($persistence);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();
        $scheduler3->reload();
        $scheduler4->reload();

        self::assertInstanceOf(\Datetime::class, $scheduler1->get('last_executed'));
        self::assertInstanceOf(\Datetime::class, $scheduler2->get('last_executed'));
        self::assertNull($scheduler3->get('last_executed'));
        self::assertNull($scheduler4->get('last_executed'));
    }

    public function testNonActiveCronInRun()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 5,
                'time_monthly' => $testTime,
            ]
        );

        $scheduler1->set('is_active', 0);
        $scheduler1->save();
        $executor = new Executor($persistence);
        $executor->run($testTime);

        $scheduler1->reload();
        self::assertNull($scheduler1->get('last_executed'));

        $scheduler1->set('is_active', 1);
        $scheduler1->save();
        $executor = new Executor($persistence);
        $executor->run($testTime);

        $scheduler1->reload();
        self::assertInstanceOf(\Datetime::class, $scheduler1->get('last_executed'));
    }

    public function testExceptionInExecuteDoesNotStopExecutionOfOthers()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);

        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );
        $scheduler1->set('cronjob_class', SomeCronJobWithExceptionInExecute::class);
        $scheduler1->save();

        $scheduler2 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'DAILY',
                'time_daily' => $testTime,
            ]
        );

        $executor = new Executor($persistence);
        $executor->run($testTime);

        $scheduler1->reload();
        $scheduler2->reload();

        self::assertInstanceOf(\DateTime::class, $scheduler2->get('last_executed'));
        self::assertInstanceOf(\DateTime::class, $scheduler1->get('last_executed'));
    }

    private function _getScheduler(Persistence $persistence, array $set = []): Scheduler
    {
        $scheduler= (new Scheduler($persistence))->createEntity();

        $scheduler->set('cronjob_class', SomeCronJob::class);
        $scheduler->set('is_active', 1);
        $scheduler->setMulti($set);

        $scheduler->save();

        return $scheduler;
    }
}