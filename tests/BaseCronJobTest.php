<?php

declare(strict_types=1);

namespace cronforatk\tests;

use atkextendedtestcase\TestCase;
use cronforatk\BaseCronJob;
use cronforatk\tests\testclasses\SomeCronJob;
use cronforatk\tests\testclasses\SomeCronJobWithExceptionInExecute;

class BaseCronJobTest extends TestCase
{

    public function testGetName(): void
    {
        self::assertSame(
            'SomeNameForThisCron',
            SomeCronJob::getName()
        );

        self::assertSame(
            'SomeCronJobWithExceptionInExecute',
            SomeCronJobWithExceptionInExecute::getName()
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
