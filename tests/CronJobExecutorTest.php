<?php

declare(strict_types=1);

namespace cronforatk\tests;

use Atk4\Data\Persistence;
use atkextendedtestcase\TestCase;
use cronforatk\CronJobExecutor;
use cronforatk\CronJobModel;
use cronforatk\tests\testclasses\SomeCronJob;
use cronforatk\tests\testclasses\SomeCronJobWithExceptionInExecute;

class CronJobExecutorTest extends TestCase
{

    protected array $sqlitePersistenceModels = [
        CronJobModel::class
    ];


    public function testRunYearly(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-11-05');
        $testTime->setTime(3, 11);
        //this one should be executed
        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime
            ]
        );
        $cronJobModel2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => (clone $testTime)->modify('-1 Minute'),
            ]
        );
        $cronJobModel3 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => (clone $testTime)->modify('+1 Minute'),
            ]
        );
        $cronJobModel4 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => (clone $testTime)->modify('+1 Day'),
                'time_yearly' => $testTime,
            ]
        );

        //only one should be executed
        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        $cronJobModel1->reload();
        $cronJobModel2->reload();
        $cronJobModel3->reload();
        $cronJobModel4->reload();

        self::assertInstanceOf(\Datetime::class, $cronJobModel1->get('last_executed'));
        self::assertNull($cronJobModel2->get('last_executed'));
        self::assertNull($cronJobModel3->get('last_executed'));
        self::assertNull($cronJobModel4->get('last_executed'));
    }

    public function testSkipYearlyIfNoDateYearlySet()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'time_yearly' => $testTime,
            ]
        );

        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        $cronJobModel1->reload();
        self::assertNull($cronJobModel1->get('last_executed'));
    }

    public function testRunMonthly(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-08-05');
        $testTime->setTime(3, 14);
        //this one should be executed
        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => (int)$testTime->format('d'),
                'time_monthly' => $testTime,
            ]
        );
        $cronJobModel2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => (int)$testTime->format('d'),
                'time_monthly' => (clone $testTime)->modify('-1 Minute'),
            ]
        );
        $cronJobModel3 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => (int)$testTime->format('d'),
                'time_monthly' => (clone $testTime)->modify('+1 Minute'),
            ]
        );
        $cronJobModel4 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => (int)(clone $testTime)->modify('+1 Day')->format('d'),
                'time_monthly' => $testTime
            ]
        );
        $cronJobModel5 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => (int)(clone $testTime)->modify('+1 Day')->format('d'),
                'time_monthly' => $testTime,
            ]
        );

        //only one should be executed
        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        $cronJobModel1->reload();
        $cronJobModel2->reload();
        $cronJobModel3->reload();
        $cronJobModel4->reload();
        $cronJobModel5->reload();

        self::assertInstanceOf(\Datetime::class, $cronJobModel1->get('last_executed'));
        self::assertNull($cronJobModel2->get('last_executed'));
        self::assertNull($cronJobModel3->get('last_executed'));
        self::assertNull($cronJobModel4->get('last_executed'));
        self::assertNull($cronJobModel5->get('last_executed'));
    }

    public function testSkipMonthlyIfNoTimeSet()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 5,
            ]
        );

        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        $cronJobModel1->reload();
        self::assertNull($cronJobModel1->get('last_executed'));
    }

    public function testRunWeekly(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-06-03');
        $testTime->setTime(4, 34);
        //this one should be executed
        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => (int)$testTime->format('N'),
                'time_weekly' => $testTime,
            ]
        );
        $cronJobModel2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => (int)$testTime->format('N'),
                'time_weekly' => (clone $testTime)->modify('-1 Minute'),
            ]
        );
        $cronJobModel3 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => (int)$testTime->format('N'),
                'time_weekly' => (clone $testTime)->modify('+1 Minute'),
            ]
        );
        $cronJobModel4 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => (int)(clone $testTime)->modify('-1 Day')->format('N'),
                'time_weekly' => $testTime,
            ]
        );
        $cronJobModel5 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => (int)(clone $testTime)->modify('+1 Day')->format('N'),
                'time_weekly' => $testTime,
            ]
        );

        //only one should be executed
        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        $cronJobModel1->reload();
        $cronJobModel2->reload();
        $cronJobModel3->reload();
        $cronJobModel4->reload();
        $cronJobModel5->reload();

        self::assertInstanceOf(\Datetime::class, $cronJobModel1->get('last_executed'));
        self::assertNull($cronJobModel2->get('last_executed'));
        self::assertNull($cronJobModel3->get('last_executed'));
        self::assertNull($cronJobModel4->get('last_executed'));
        self::assertNull($cronJobModel5->get('last_executed'));
    }

    public function testRunDaily(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime();
        $testTime->setTime(15, 3);

        //this one should be executed
        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'DAILY',
                'time_daily' => $testTime,
            ]
        );
        $cronJobModel2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'DAILY',
                'time_daily' => (clone $testTime)->modify('-1 Minute')
            ]
        );
        $cronJobModel3 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'DAILY',
                'time_daily' => (clone $testTime)->modify('+1 Minute')
            ]
        );

        //only one should be executed
        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        $cronJobModel1->reload();
        $cronJobModel2->reload();
        $cronJobModel3->reload();

        self::assertInstanceOf(\Datetime::class, $cronJobModel1->get('last_executed'));
        self::assertNull($cronJobModel2->get('last_executed'));
        self::assertNull($cronJobModel3->get('last_executed'));
    }

    public function testRunHourly()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime();
        $testTime->setTime(14, 35);
        //this one should be executed
        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'HOURLY',
                'minute_hourly' => (int)$testTime->format('i')
            ]
        );
        var_dump($cronJobModel1->get('minute_hourly'));
        $cronJobModel2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'HOURLY',
                'minute_hourly' => (int)(clone $testTime)->modify('+1 Minute')->format('i')
            ]
        );
        $cronJobModel3 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'HOURLY',
                'minute_hourly' => (int)(clone $testTime)->modify('-1 Minute')->format('i')
            ]
        );

        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        $cronJobModel1->reload();
        $cronJobModel2->reload();
        $cronJobModel3->reload();

        self::assertInstanceOf(\Datetime::class, $cronJobModel1->get('last_executed'));
        self::assertNull($cronJobModel2->get('last_executed'));
        self::assertNull($cronJobModel3->get('last_executed'));
    }

    public function testRunMinutely()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime();
        $testTime->setTime(3, 16);
        //this one should be executed
        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_MINUTE',
            ]
        );
        $cronJobModel2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTH_MINUTE',
            ]
        );
        $cronJobModel3 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTEENTH_MINUTE',
            ]
        );

        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        $cronJobModel1->reload();
        $cronJobModel2->reload();
        $cronJobModel3->reload();

        self::assertInstanceOf(\Datetime::class, $cronJobModel1->get('last_executed'));
        self::assertNull($cronJobModel2->get('last_executed'));
        self::assertNull($cronJobModel3->get('last_executed'));
    }

    public function testRunMinutelyOffset()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime();
        $testTime->setTime(3, 18);

        //this one should be executed
        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTH_MINUTE',
                'offset_minutely' => 3,
            ]
        );
        //this one should be executed, too
        $cronJobModel2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTEENTH_MINUTE',
                'offset_minutely' => 3,
            ]
        );
        $cronJobModel3 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTH_MINUTE',
            ]
        );
        $cronJobModel4 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTEENTH_MINUTE',
            ]
        );

        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        $cronJobModel1->reload();
        $cronJobModel2->reload();
        $cronJobModel3->reload();
        $cronJobModel4->reload();

        self::assertInstanceOf(\Datetime::class, $cronJobModel1->get('last_executed'));
        self::assertInstanceOf(\Datetime::class, $cronJobModel2->get('last_executed'));
        self::assertNull($cronJobModel3->get('last_executed'));
        self::assertNull($cronJobModel4->get('last_executed'));
    }


    public function testLastExecutedSaved()
    {
        $persistence = $this->getSqliteTestPersistence();
        //this one should be executed
        $cronJobExecutor0 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_MINUTE',
            ]
        );

        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run();

        $cronJobExecutor0->reload();
        self::assertSame(
            (new \DateTime())->format('d-m-Y H:i:s'),
            $cronJobExecutor0->get('last_executed')->format('d-m-Y H:i:s')
        );
    }

    public function testNonActiveCronInRun()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 5,
                'time_monthly' => $testTime,
            ]
        );

        $cronJobModel1->set('is_active', 0);
        $cronJobModel1->save();
        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        $cronJobModel1->reload();
        self::assertNull($cronJobModel1->get('last_executed'));

        $cronJobModel1->set('is_active', 1);
        $cronJobModel1->save();
        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        $cronJobModel1->reload();
        self::assertInstanceOf(\Datetime::class, $cronJobModel1->get('last_executed'));
    }

    public function testExceptionInExecuteDoesNotStopExecutionOfOthers()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);

        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );
        $cronJobModel1->set('cronjob_class', SomeCronJobWithExceptionInExecute::class);
        $cronJobModel1->save();

        $cronJobModel2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'DAILY',
                'time_daily' => $testTime,
            ]
        );


        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        $cronJobModel1->reload();
        $cronJobModel2->reload();

        self::assertInstanceOf(\DateTime::class, $cronJobModel2->get('last_executed'));
        self::assertEquals(
            true,
            $cronJobModel2->get('last_execution_success')
        );
        self::assertInstanceOf(\DateTime::class, $cronJobModel1->get('last_executed'));
        self::assertEquals(
            false,
            $cronJobModel1->get('last_execution_success')
        );
    }

    public function testDurationIsMonitored()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);

        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );

        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        $cronJobModel1->reload();

        self::assertEqualsWithDelta(
            1.0,
            $cronJobModel1->get('last_execution_duration'),
            0.05
        );
    }

    private function _getRecord(Persistence $persistence, array $set = []): CronJobModel
    {
        $cronJobModel = (new CronJobModel($persistence))->createEntity();

        $cronJobModel->set('cronjob_class', SomeCronJob::class);
        $cronJobModel->set('is_active', 1);
        $cronJobModel->setMulti($set);

        $cronJobModel->save();

        return $cronJobModel;
    }
}