<?php

declare(strict_types=1);

namespace cronforatk\tests;

use atkextendedtestcase\TestCase;
use cronforatk\BaseCronJob;
use cronforatk\tests\testclasses\SomeCronJob;
use cronforatk\tests\testclasses\SomeCronJobWithoutExecute;

class BaseCronJobTest extends TestCase
{

    public function testExceptionNoExecuteImplementedInDescendant(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $cron = new SomeCronJobWithoutExecute($persistence);
        self::expectExceptionMessage('execute needs to ne implemented in descendants of cronforatk\BaseCronJob');
        $cron->execute();
    }

    public function testGetName(): void
    {
        self::assertSame(
            'SomeNameForThisCron',
            SomeCronJob::getName()
        );

        self::assertSame(
            'SomeCronJobWithoutExecute',
            SomeCronJobWithoutExecute::getName()
        );
    }

    public function testGetDescription(): void
    {
        self::assertSame(
            '',
            BaseCronJob::getDescription()
        );

        self::assertSame(
            'SomeDescriptionExplainingWhatThisIsDoing',
            SomeCronJob::getDescription()
        );
    }
}
