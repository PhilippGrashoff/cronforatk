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
        self::assertCount(2, $resultOneDir);
        self::assertArrayHasKey('PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJobWithExceptionInExecute', $resultOneDir);
        self::assertArrayHasKey('PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJob', $resultOneDir);
        self::assertContains('SomeCronJobWithExceptionInExecute', $resultOneDir);
        self::assertContains('SomeNameForThisCron', $resultOneDir);
    }

    public function testLoadAvailableCronJobsFrom2Directories(): void
    {
        $resultTwoDirs = CronJobLoader::getAvailableCronJobs(
            [
                __DIR__ . '/Testclasses' => 'PhilippR\\Atk4\\Cron\\Tests\\Testclasses',
                __DIR__ . '/Testclasses2' => 'PhilippR\\Atk4\\Cron\\Tests\\Testclasses2'
            ]
        );

        self::assertCount(3, $resultTwoDirs);
        self::assertArrayHasKey('PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJobWithExceptionInExecute', $resultTwoDirs);
        self::assertArrayHasKey('PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJob', $resultTwoDirs);
        self::assertArrayHasKey('PhilippR\Atk4\Cron\Tests\Testclasses2\SomeOtherCronJob', $resultTwoDirs);
        self::assertContains('SomeCronJobWithExceptionInExecute', $resultTwoDirs);
        self::assertContains('SomeNameForThisCron', $resultTwoDirs);
        self::assertContains('SomeNameForThisOtherCron', $resultTwoDirs);
    }

    public function testNonExistentFolderIsSkipped(): void
    {
        $resultOneDir = CronJobLoader::getAvailableCronJobs(
            [
                __DIR__ . '/Testclasses' => 'PhilippR\\Atk4\\Cron\\Tests\\Testclasses',
                'some/non/existant/path' => 'PMRAtk\\Data\\Cron',
            ]
        );

        self::assertCount(2, $resultOneDir);
    }
}