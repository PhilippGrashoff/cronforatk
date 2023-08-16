<?php

declare(strict_types=1);

namespace cronforatk\tests;

use atkextendedtestcase\TestCase;
use cronforatk\tests\testclasses\SomeCronJob;
use cronforatk\tests\testclasses\SomeCronJobWithoutExecute;

class BaseCronJobTest extends TestCase
{

    public function testExceptionNoExecuteImplementedInDescendant(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $cron = new SomeCronJobWithoutExecute($persistence);
        self::expectExceptionMessage('SomeException');
        $cron->execute();
    }

    public function testGetName(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $cron = new SomeCronJob($persistence);
        self::assertSame(
            'SomeCronJob',
            $cron->getName()
        );

        $cron->name = 'SOMENAME';
        self::assertSame(
            'SOMENAME',
            $cron->getName()
        );
    }
}
