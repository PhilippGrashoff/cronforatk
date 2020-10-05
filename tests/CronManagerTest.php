<?php

declare(strict_types=1);

namespace cronforatk\tests;

use PMRAtk\tests\TestClasses\Persistence\ArrayWithApp;
use atk4\data\Persistence;
use DateTime;
use PMRAtk\Data\Cron\CronManager;
use PMRAtk\Data\Cron\BaseCronJob;
use PMRAtk\tests\phpunit\TestCase;
use PMRAtk\tests\TestClasses\CronTestClasses\SampleCron;


class CronManagerTest extends TestCase
{

    public function testGetAvailableCrons()
    {
        $cm = $this->_getRecord([]);
        $res = $cm->getAvailableCrons();
        self::assertTrue(array_key_exists(SampleCron::class, $res));
        self::assertFalse(array_key_exists(CronManager::class, $res));
    }

    public function testExecuteCron()
    {
        $this->_addStandardEmailAccount();
        $cm = $this->_getRecord(
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_MINUTE',
            ]
        );
        $cm->executeCron();
        self::assertEquals(1, count($cm->executedCrons));
        $cm->executeCron();
        self::assertEquals(1, count($cm->executedCrons));
        self::assertEquals(2, count($cm->executedCrons[SampleCron::class]));
    }

    public function testRunYearly()
    {
        $this->_addStandardEmailAccount();
        $testTime = new DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $cm1 = $this->_getRecord(
            [
                'interval' => 'YEARLY',
                'date_yearly' => '2020-05-05',
                'time_yearly' => '03:03',
            ]
        );
        $cm2 = $this->_getRecord(
            [
                'interval' => 'YEARLY',
                'date_yearly' => '2020-05-05',
                'time_yearly' => '03:04',
            ]
        );
        $cm3 = $this->_getRecord(
            [
                'interval' => 'YEARLY',
                'date_yearly' => '2020-05-05',
                'time_yearly' => '03:02',
            ]
        );
        $cm3 = $this->_getRecord(
            [
                'interval' => 'YEARLY',
                'date_yearly' => '2020-05-06',
                'time_yearly' => '03:03',
            ]
        );
        $cm3 = $this->_getRecord(
            [
                'interval' => 'YEARLY',
                'date_yearly' => '2020-06-05',
                'time_yearly' => '03:03',
            ]
        );

        //only one should be executed
        $cm = new CronManager(self::$app->db);
        $cm->run($testTime);
        self::assertEquals(1, count($cm->executedCrons[SampleCron::class]));
    }

    public function testRunMonthly()
    {
        $this->_addStandardEmailAccount();
        $testTime = new DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $cm1 = $this->_getRecord(
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 5,
                'time_monthly' => '03:03',
            ]
        );
        $cm2 = $this->_getRecord(
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 5,
                'time_monthly' => '03:02',
            ]
        );
        $cm3 = $this->_getRecord(
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 5,
                'time_monthly' => '03:04',
            ]
        );
        $cm3 = $this->_getRecord(
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 4,
                'time_monthly' => '03:03',
            ]
        );
        $cm3 = $this->_getRecord(
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 6,
                'time_monthly' => '03:03',
            ]
        );

        //only one should be executed
        $cm = new CronManager(self::$app->db);
        $cm->run($testTime);
        self::assertEquals(1, count($cm->executedCrons[SampleCron::class]));
    }

    public function testRunWeekly()
    {
        $this->_addStandardEmailAccount();
        $testTime = new DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $cm1 = $this->_getRecord(
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => 2,
                'time_weekly' => '03:03',
            ]
        );
        $cm2 = $this->_getRecord(
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => 2,
                'time_weekly' => '03:02',
            ]
        );
        $cm3 = $this->_getRecord(
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => 2,
                'time_weekly' => '03:04',
            ]
        );
        $cm3 = $this->_getRecord(
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => 1,
                'time_weekly' => '03:03',
            ]
        );
        $cm3 = $this->_getRecord(
            [
                'interval' => 'WEEKLY',
                'weekday_weekly' => 3,
                'time_weekly' => '03:03',
            ]
        );

        //only one should be executed
        $cm = new CronManager(self::$app->db);
        $cm->run($testTime);
        self::assertEquals(1, count($cm->executedCrons[SampleCron::class]));
    }

    public function testRunDaily()
    {
        $this->_addStandardEmailAccount();
        $testTime = new DateTime();
        $testTime->setTime(3, 3);
        //this one should be executed
        $cm1 = $this->_getRecord(
            [
                'interval' => 'DAILY',
                'time_daily' => '03:03',
            ]
        );
        $cm2 = $this->_getRecord(
            [
                'interval' => 'DAILY',
                'time_daily' => '03:02',
            ]
        );
        $cm3 = $this->_getRecord(
            [
                'interval' => 'DAILY',
                'time_daily' => '03:04',
            ]
        );

        //only one should be executed
        $cm = new CronManager(self::$app->db);
        $cm->run($testTime);
        self::assertEquals(1, count($cm->executedCrons[SampleCron::class]));
    }

    public function testRunHourly()
    {
        $this->_addStandardEmailAccount();
        $testTime = new DateTime();
        $testTime->setTime(3, 3);
        //this one should be executed
        $cm0 = $this->_getRecord(
            [
                'interval' => 'HOURLY',
                'minute_hourly' => 3,
            ]
        );
        //this one should be executed
        $cm1 = $this->_getRecord(
            [
                'interval' => 'HOURLY',
                'minute_hourly' => 3,
            ]
        );
        $cm2 = $this->_getRecord(
            [
                'interval' => 'HOURLY',
                'minute_hourly' => 2,
            ]
        );
        $cm3 = $this->_getRecord(
            [
                'interval' => 'HOURLY',
                'minute_hourly' => 4,
            ]
        );


        $cm = new CronManager(self::$app->db);
        $cm->run($testTime);
        self::assertEquals(2, count($cm->executedCrons[SampleCron::class]));
    }

    public function testRunMinutely()
    {
        $this->_addStandardEmailAccount();
        $testTime = new DateTime();
        $testTime->setTime(3, 16);
        //this one should be executed
        $cm0 = $this->_getRecord(
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_MINUTE',
            ]
        );
        $cm1 = $this->_getRecord(
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTH_MINUTE',
            ]
        );
        $cm3 = $this->_getRecord(
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTEENTH_MINUTE',
            ]
        );


        $cm = new CronManager(self::$app->db);
        $cm->run($testTime);
        self::assertEquals(1, count($cm->executedCrons[SampleCron::class]));
    }

    public function testSkipYearlyIfNoDateTime()
    {
        $this->_addStandardEmailAccount();
        $testTime = new DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $cm1 = $this->_getRecord(
            [
                'interval' => 'YEARLY',
                'time_yearly' => '03:03',
            ]
        );

        $cm = new CronManager(self::$app->db);
        $cm->run($testTime);
        self::assertEquals(0, count($cm->executedCrons));
    }

    public function testSkipMonthlyIfNoDateTime()
    {
        $this->_addStandardEmailAccount();
        $testTime = new DateTime('2020-05-05');
        $testTime->setTime(3, 3);
        //this one should be executed
        $cm1 = $this->_getRecord(
            [
                'interval' => 'MONTHLY',
                'day_monthly' => 5,
            ]
        );

        $cm = new CronManager(self::$app->db);
        $cm->run($testTime);
        self::assertEquals(0, count($cm->executedCrons));
    }

    public function testLastExecutedSaved()
    {
        $this->_addStandardEmailAccount();
        //this one should be executed
        $cm0 = $this->_getRecord(
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_MINUTE',
            ]
        );

        $cm = new CronManager(self::$app->db);
        $cm->run();

        $cm0->reload();
        self::assertEquals((new DateTime())->format('d.m.Y H:i:s'), $cm0->get('last_executed')['last_executed']);
    }

    public function testRunMinutelyOffset()
    {
        $this->_addStandardEmailAccount();
        $testTime = new DateTime();
        $testTime->setTime(3, 18);
        //this one should be executed
        $cm1 = $this->_getRecord(
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTH_MINUTE',
                'offset_minutely' => 3,
            ]
        );
        //this one should be executed
        $cm3 = $this->_getRecord(
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTEENTH_MINUTE',
                'offset_minutely' => 3,
            ]
        );
        $cm2 = $this->_getRecord(
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTH_MINUTE',
            ]
        );
        $cm4 = $this->_getRecord(
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_FIFTEENTH_MINUTE',
            ]
        );


        $cm = new CronManager(self::$app->db);
        $cm->run($testTime);
        self::assertEquals(2, count($cm->executedCrons[SampleCron::class]));
    }

    public function testDescriptionLoadedOnInsert()
    {
        $this->_addStandardEmailAccount();
        $cm = new CronManager(self::$app->db);
        $cm->set('name', SampleCron::class);
        $cm->save();
        self::assertEquals($cm->get('description'), 'SomeDescriptionExplainingWhatThisIsDoing');
    }

    public function testNonExistantClassName()
    {
        $cm = $this->_getRecord([]);
        $desc = $cm->get('description');
        $cm->set('name', 'LALADU');
        $cm->save();
        self::assertEquals($desc, $cm->get('description'));
    }

    public function testNonActiveCronInRun()
    {
        $cm = $this->_getRecord([]);
        $cm->set('is_active', 0);
        $cm->save();

        $cm = new CronManager(self::$app->db);
        $cm->run();
        self::assertEquals(0, count($cm->executedCrons));
    }

    public function testNonExistantClassNameReturnsFalseOnExecuteCron()
    {
        $cm = $this->_getRecord([]);
        $cm->set('name', 'LALALA');
        self::assertFalse($cm->executeCron());
    }

    public function testNonExistantFolderIsSkipped()
    {
        $this->_addStandardEmailAccount();
        $cm = new CronManager(
            self::$app->db, [
            'cronFilesPath' => [
                'some/non/existant/path' => 'PMRAtk\\Data\\Cron',
                'tests/TestClasses/CronTestClasses' => 'PMRAtk\\tests\\TestClasses\\CronTestClasses',
            ]
        ]
        );
        self::assertEquals(4, count($cm->getAvailableCrons()));
    }

    public function testSorting()
    {
        $this->_addStandardEmailAccount();
        $this->_getRecord(
            [
                'interval' => 'DAILY',
            ]
        );
        $this->_getRecord(
            [
                'interval' => 'MINUTELY',
            ]
        );
        $this->_getRecord(
            [
                'interval' => 'YEARLY',
            ]
        );
        $this->_getRecord(
            [
                'interval' => 'MONTHLY',
            ]
        );
        $this->_getRecord(
            [
                'interval' => 'MONTHLY',
            ]
        );
        $this->_getRecord(
            [
                'interval' => 'DAILY',
            ]
        );
        $this->_getRecord(
            [
                'interval' => null,
            ]
        );
        $this->_getRecord(
            [
                'interval' => 'YEARLY',
            ]
        );
        $this->_getRecord(
            [
                'interval' => 'HOURLY',
            ]
        );
        $this->_getRecord(
            [
                'interval' => 'WEEKLY',
            ]
        );

        $orderArray = [
            1 => 'YEARLY',
            2 => 'YEARLY',
            3 => 'MONTHLY',
            4 => 'MONTHLY',
            5 => 'WEEKLY',
            6 => 'DAILY',
            7 => 'DAILY',
            8 => 'HOURLY',
            9 => 'MINUTELY',
            10 => null,
        ];

        $counter = 0;
        foreach ((new CronManager(self::$app->db)) as $cm) {
            $counter++;
            self::assertEquals($orderArray[$counter], $cm->get('interval'));
        }
    }

    private function _getRecord(array $set = []): CronManager
    {
        $cm = new CronManager(
            self::$app->db,
            [
                'cronFilesPath' =>
                    [
                        'src/Data/Cron' => 'PMRAtk\\Data\\Cron',
                        'tests/TestClasses/CronTestClasses' => 'PMRAtk\\tests\\TestClasses\\CronTestClasses',
                    ]
            ]
        );
        $cm->set('name', SampleCron::class);
        $cm->set('is_active', 1);
        $cm->setMulti($set);
        $cm->save();
        return $cm;
    }
}