<?php

declare(strict_types=1);

namespace cronforatk\tests;

use atkextendedtestcase\TestCase;
use cronforatk\CronJobExecutor;
use cronforatk\tests\testclasses\SomeCronJob;

class CronJobLoaderTest extends TestCase
{

    protected array $sqlitePersistenceModels = [
        CronJobExecutor::class
    ];

    public function testLoadAvailableCronJobs(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $cm = $this->_getRecord($persistence, []);
        $res = $cm->getAvailableCrons();
        self::assertTrue(array_key_exists(SomeCronJob::class, $res));
        self::assertFalse(array_key_exists(CronJobExecutor::class, $res));
    }

    public function testLoadAvailableCronJobsFrom2Directories(): void
    {
    }

    public function testNonExistentFolderIsSkipped()
    {
        $cm = new CronJobExecutor(
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
}