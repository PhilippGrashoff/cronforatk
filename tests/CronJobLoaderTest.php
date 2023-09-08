<?php

declare(strict_types=1);

namespace PhilippR\Atk4\Cron\Tests;


use Atk4\Core\Phpunit\TestCase;
use PhilippR\Atk4\Cron\CronJobLoader;

class CronJobLoaderTest extends TestCase
{

    public function testLoadAvailableCronJobs(): void
    {
        $resultOneDir = CronJobLoader::getAvailableCronJobs(
            [__DIR__ . '/Testclasses' => 'PhilippR\\Atk4\\Cron\\Tests\\Testclasses']

        );
        self::assertSame(
            [
                'PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJobWithExceptionInExecute' => 'SomeCronJobWithExceptionInExecute',
                'PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJob' => 'SomeNameForThisCron'
            ],
            $resultOneDir
        );
    }

    public function testLoadAvailableCronJobsFrom2Directories(): void
    {
        $resultTwoDirs = CronJobLoader::getAvailableCronJobs(
            [
                __DIR__ . '/Testclasses' => 'PhilippR\\Atk4\\Cron\\Tests\\Testclasses',
                __DIR__ . '/Testclasses2' => 'PhilippR\\Atk4\\Cron\\Tests\\Testclasses2'
            ]
        );
        self::assertSame(
            [
                'PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJobWithExceptionInExecute' => 'SomeCronJobWithExceptionInExecute',
                'PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJob' => 'SomeNameForThisCron',
                'PhilippR\Atk4\Cron\Tests\Testclasses2\SomeOtherCronJob' => 'SomeNameForThisOtherCron'

            ],
            $resultTwoDirs
        );
    }

    public function testNonExistentFolderIsSkipped(): void
    {
        $resultOneDir = CronJobLoader::getAvailableCronJobs(
            [
                __DIR__ . '/Testclasses' => 'PhilippR\\Atk4\\Cron\\Tests\\Testclasses',
                'some/non/existant/path' => 'PMRAtk\\Data\\Cron',
            ]
        );
        self::assertSame(
            [
                'PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJobWithExceptionInExecute' => 'SomeCronJobWithExceptionInExecute',
                'PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJob' => 'SomeNameForThisCron'
            ],
            $resultOneDir
        );
    }
}