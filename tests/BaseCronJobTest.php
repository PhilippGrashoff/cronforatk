<?php

declare(strict_types=1);

namespace PhilippR\Atk4\Cron\Tests;

use atkextendedtestcase\TestCase;
use PhilippR\Atk4\Cron\BaseCronJob;
use PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJob;
use PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJobWithExceptionInExecute;

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
