<?php

declare(strict_types=1);

namespace cronforatk\tests;

use atkextendedtestcase\TestCase;
use cronforatk\CronJobLoader;

class CronJobLoaderTest extends TestCase
{

    public function testLoadAvailableCronJobs(): void
    {
        $resultOneDir = CronJobLoader::getAvailableCronJobs(
            [__DIR__ . '/testclasses' => 'cronforatk\\tests\\testclasses']

        );
        self::assertSame(
            [
                'cronforatk\tests\testclasses\SomeCronJobWithoutExecute' => 'SomeCronJobWithoutExecute',
                'cronforatk\tests\testclasses\SomeCronJobWithExceptionInExecute' => 'SomeCronJobWithExceptionInExecute',
                'cronforatk\tests\testclasses\SomeCronJob' => 'SomeNameForThisCron'
            ],
            $resultOneDir
        );
    }

    public function testLoadAvailableCronJobsFrom2Directories(): void
    {
        $resultTwoDirs = CronJobLoader::getAvailableCronJobs(
            [
                __DIR__ . '/testclasses' => 'cronforatk\\tests\\testclasses',
                __DIR__ . '/testclasses2' => 'cronforatk\\tests\\testclasses2'
            ]
        );
        self::assertSame(
            [
                'cronforatk\tests\testclasses\SomeCronJobWithoutExecute' => 'SomeCronJobWithoutExecute',
                'cronforatk\tests\testclasses\SomeCronJobWithExceptionInExecute' => 'SomeCronJobWithExceptionInExecute',
                'cronforatk\tests\testclasses\SomeCronJob' => 'SomeNameForThisCron',
                'cronforatk\tests\testclasses2\SomeOtherCronJob' => 'SomeNameForThisOtherCron'

            ],
            $resultTwoDirs
        );
    }

    public function testNonExistentFolderIsSkipped(): void
    {
        $resultOneDir = CronJobLoader::getAvailableCronJobs(
            [
                __DIR__ . '/testclasses' => 'cronforatk\\tests\\testclasses',
                'some/non/existant/path' => 'PMRAtk\\Data\\Cron',
            ]
        );
        self::assertSame(
            [
                'cronforatk\tests\testclasses\SomeCronJobWithoutExecute' => 'SomeCronJobWithoutExecute',
                'cronforatk\tests\testclasses\SomeCronJobWithExceptionInExecute' => 'SomeCronJobWithExceptionInExecute',
                'cronforatk\tests\testclasses\SomeCronJob' => 'SomeNameForThisCron'
            ],
            $resultOneDir
        );
    }
}