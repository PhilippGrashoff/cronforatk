<?php

declare(strict_types=1);

namespace cronforatk\tests;

use Atk4\Data\Exception;
use cronforatk\tests\testclasses\SomeCronJobWithExceptionInExecute;
use traitsforatkdata\TestCase;
use cronforatk\CronExecutor;
use Atk4\Data\Persistence;
use cronforatk\tests\testclasses\SomeCronJob;

class CronExecutorTest extends TestCase
{

    protected $sqlitePersistenceModels = [
        CronExecutor::class
    ];

    public function testGetAvailableCrons()
    {
        $persistence = $this->getSqliteTestPersistence();
        $cm = $this->_getRecord($persistence, []);
        $res = $cm->getAvailableCrons();
        self::assertTrue(array_key_exists(SomeCronJob::class, $res));
        self::assertFalse(array_key_exists(CronExecutor::class, $res));
    }

    public function testRunYearly()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $cm1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => '2020-05-05',
                'time_yearly' => '03:03',
            ]
        );
        $cm2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => '2020-05-05',
                'time_yearly' => '03:04',
            ]
        );
        $cm3 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => '2020-05-05',
                'time_yearly' => '03:02',
            ]
        );
        $cm4 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => '2020-05-06',
                'time_yearly' => '03:03',
            ]
        );
        $cm5 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => '2020-06-05',
                'time_yearly' => '03:03',
            ]
        );

        //only one should be executed
        $cm = new CronExecutor($persistence);
        $cm->run($testTime);

        $cm1->reload();
        $cm2->reload();
        $cm3->reload();
        $cm4->reload();
        $cm5->reload();

        self::assertInstanceOf(\Datetime::class, $cm1->get('last_executed'));
        self::assertNull($cm2->get('last_executed'));
        self::assertNull($cm3->get('last_executed'));
        self::assertNull($cm4->get('last_executed'));
        self::assertNull($cm5->get('last_executed'));
    }

    public function testRunMonthly()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $cm1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 5,
                'time_monthly' => '03:03',
            ]
        );
        $cm2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 5,
                'time_monthly' => '03:02',
            ]
        );
        $cm3 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 5,
                'time_monthly' => '03:04',
            ]
        );
        $cm4 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 4,
                'time_monthly' => '03:03',
            ]
        );
        $cm5 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 6,
                'time_monthly' => '03:03',
            ]
        );

        //only one should be executed
        $cm = new CronExecutor($persistence);
        $cm->run($testTime);

        $cm1->reload();
        $cm2->reload();
        $cm3->reload();
        $cm4->reload();
        $cm5->reload();

        self::assertInstanceOf(\Datetime::class, $cm1->get('last_executed'));
        self::assertNull($cm2->get('last_executed'));
        self::assertNull($cm3->get('last_executed'));
        self::assertNull($cm4->get('last_executed'));
        self::assertNull($cm5->get('last_executed'));
    }


    public function testRunWeekly()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $cm1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => 2,
                'time_weekly' => '03:03',
            ]
        );
        $cm2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => 2,
                'time_weekly' => '03:02',
            ]
        );
        $cm3 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => 2,
                'time_weekly' => '03:04',
            ]
        );
        $cm4 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => 1,
                'time_weekly' => '03:03',
            ]
        );
        $cm5 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => 3,
                'time_weekly' => '03:03',
            ]
        );

        //only one should be executed
        $cm = new CronExecutor($persistence);
        $cm->run($testTime);

        $cm1->reload();
        $cm2->reload();
        $cm3->reload();
        $cm4->reload();
        $cm5->reload();

        self::assertInstanceOf(\Datetime::class, $cm1->get('last_executed'));
        self::assertNull($cm2->get('last_executed'));
        self::assertNull($cm3->get('last_executed'));
        self::assertNull($cm4->get('last_executed'));
        self::assertNull($cm5->get('last_executed'));
    }

    public function testRunDaily()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime();
        $testTime->setTime(3, 3);

        //this one should be executed
        $cm1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'DAILY',
                'time_daily' => '03:03',
            ]
        );
        $cm2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'DAILY',
                'time_daily' => '03:02',
            ]
        );
        $cm3 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'DAILY',
                'time_daily' => '03:04',
            ]
        );

        //only one should be executed
        $cm = new CronExecutor($persistence);
        $cm->run($testTime);

        $cm1->reload();
        $cm2->reload();
        $cm3->reload();

        self::assertInstanceOf(\Datetime::class, $cm1->get('last_executed'));
        self::assertNull($cm2->get('last_executed'));
        self::assertNull($cm3->get('last_executed'));
    }

    public function testRunHourly()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime();
        $testTime->setTime(3, 3);
        //this one should be executed
        $cm1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'HOURLY',
                'minute_hourly' => 3,
            ]
        );
        $cm2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'HOURLY',
                'minute_hourly' => 2,
            ]
        );
        $cm3 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'HOURLY',
                'minute_hourly' => 4,
            ]
        );


        $cm = new CronExecutor($persistence);
        $cm->run($testTime);

        $cm1->reload();
        $cm2->reload();
        $cm3->reload();

        self::assertInstanceOf(\Datetime::class, $cm1->get('last_executed'));
        self::assertNull($cm2->get('last_executed'));
        self::assertNull($cm3->get('last_executed'));
    }

    public function testRunMinutely()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime();
        $testTime->setTime(3, 16);
        //this one should be executed
        $cm1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_MINUTE',
            ]
        );
        $cm2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTH_MINUTE',
            ]
        );
        $cm3 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTEENTH_MINUTE',
            ]
        );

        $cm = new CronExecutor($persistence);
        $cm->run($testTime);

        $cm1->reload();
        $cm2->reload();
        $cm3->reload();

        self::assertInstanceOf(\Datetime::class, $cm1->get('last_executed'));
        self::assertNull($cm2->get('last_executed'));
        self::assertNull($cm3->get('last_executed'));
    }

    public function testSkipYearlyIfNoDateYearlySet()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $cm1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'time_yearly' => '03:03',
            ]
        );

        $cm = new CronExecutor($persistence);
        $cm->run($testTime);

        $cm1->reload();
        self::assertNull($cm1->get('last_executed'));
    }

    public function testSkipMonthlyIfNoTimeSet()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $cm1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 5,
            ]
        );

        $cm = new CronExecutor($persistence);
        $cm->run($testTime);

        $cm1->reload();
        self::assertNull($cm1->get('last_executed'));
    }

    public function testLastExecutedSaved()
    {
        $persistence = $this->getSqliteTestPersistence();
        //this one should be executed
        $cm0 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_MINUTE',
            ]
        );

        $cm = new CronExecutor($persistence);
        $cm->run();

        $cm0->reload();
        self::assertEquals(
            (new \DateTime())->modify('-1 Second')->format('d-m-Y H:i:s'),
            $cm0->get('last_executed')->format('d-m-Y H:i:s')
        );
    }

    public function testRunMinutelyOffset()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime();
        $testTime->setTime(3, 18);

        //this one should be executed
        $cm1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTH_MINUTE',
                'offset_minutely' => 3,
            ]
        );
        //this one should be executed, too
        $cm2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTEENTH_MINUTE',
                'offset_minutely' => 3,
            ]
        );
        $cm3 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTH_MINUTE',
            ]
        );
        $cm4 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTEENTH_MINUTE',
            ]
        );

        $cm = new CronExecutor($persistence);
        $cm->run($testTime);

        $cm1->reload();
        $cm2->reload();
        $cm3->reload();
        $cm4->reload();

        self::assertInstanceOf(\Datetime::class, $cm1->get('last_executed'));
        self::assertInstanceOf(\Datetime::class, $cm2->get('last_executed'));
        self::assertNull($cm3->get('last_executed'));
        self::assertNull($cm4->get('last_executed'));
    }

    public function testDescriptionLoadedOnInsert()
    {
        $cm = new CronExecutor($this->getSqliteTestPersistence());
        $cm->set('type', SomeCronJob::class);
        $cm->save();
        self::assertEquals(
            'SomeDescriptionExplainingWhatThisIsDoing',
            $cm->get('description')
        );
    }

    public function testNonActiveCronInRun()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $cm1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 5,
                'time_monthly' => '03:03',
            ]
        );

        $cm1->set('is_active', 0);
        $cm1->save();
        $cm = new CronExecutor($persistence);
        $cm->run($testTime);
        $cm1->reload();
        self::assertNull($cm1->get('last_executed'));

        $cm1->set('is_active', 1);
        $cm1->save();
        $cm = new CronExecutor($persistence);
        $cm->run($testTime);
        $cm1->reload();

        self::assertInstanceOf(\Datetime::class, $cm1->get('last_executed'));
    }

    public function testNonExistantFolderIsSkipped()
    {
        $cm = new CronExecutor(
            $this->getSqliteTestPersistence(),
            [
                'cronFilesPath' => [
                    'some/non/existant/path' => 'PMRAtk\\Data\\Cron',
                    '/tests/testclasses/' => 'cronforatk\\tests\\testclasses',
                ]
            ]
        );
        self::assertEquals(3, count($cm->getAvailableCrons()));
    }

    public function testExceptionInExecuteDoesNotStopExecutionOfOthers() {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);

        $cm1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => '2020-05-05',
                'time_yearly' => '03:03',
            ]
        );
        $cm1->set('type', SomeCronJobWithExceptionInExecute::class);
        $cm1->save();

        $cm2 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'DAILY',
                'time_daily' => '03:03',
            ]
        );


        $cm = new CronExecutor($persistence);
        $cm->run($testTime);

        $cm1->reload();
        $cm2->reload();

        self::assertInstanceOf(\DateTime::class, $cm2->get('last_executed'));
        self::assertEquals(
            true,
            $cm2->get('last_execution_success')
        );
        self::assertInstanceOf(\DateTime::class, $cm1->get('last_executed'));
        self::assertEquals(
            false,
            $cm1->get('last_execution_success')
        );
    }

    public function testDurationIsMonitored() {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);

        $cm1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => '2020-05-05',
                'time_yearly' => '03:03',
            ]
        );

        $cm = new CronExecutor($persistence);
        $cm->run($testTime);

        $cm1->reload();

        self::assertEqualsWithDelta(
            1.0,
            $cm1->get('last_execution_duration'),
            0.05
        );
    }

    public function testExceptionExecuteCronThisNotLoaded() {
        $persitence = $this->getSqliteTestPersistence();
        $cr = new CronExecutor($persitence);
        self::assertFalse(
            $cr->executeCron()
        );
    }


    private function _getRecord(Persistence $persistence, array $set = []): CronExecutor
    {
        $cm = new CronExecutor(
            $persistence,
            [
                'cronFilesPath' =>
                    [
                        '/tests/testclasses/' => 'cronforatk\\tests\\testclasses',
                    ]
            ]
        );

        $cm->set('type', SomeCronJob::class);
        $cm->set('is_active', 1);
        $cm->setMulti($set);

        $cm->save();

        return $cm;
    }
}