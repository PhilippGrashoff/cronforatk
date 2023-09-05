<?php

declare(strict_types=1);

namespace PhilippR\Atk4\Cron\Tests;

use atkextendedtestcase\TestCase;
use PhilippR\Atk4\Cron\ExecutionLog;
use PhilippR\Atk4\Cron\Scheduler;
use PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJob;

class SchedulerTest extends TestCase
{

    protected array $sqlitePersistenceModels = [
        Scheduler::class,
        ExecutionLog::class
    ];

    public function testNameAndDescriptionLoadedFromBaseCronJob()
    {
        $scheduler = (new Scheduler($this->getSqliteTestPersistence()))->createEntity();
        $scheduler->set('cronjob_class', SomeCronJob::class);
        $scheduler->save();
        self::assertSame(
            'SomeNameForThisCron',
            $scheduler->get('name')
        );
        self::assertSame(
            'SomeDescriptionExplainingWhatThisIsDoing',
            $scheduler->get('description')
        );
    }

    public function testNameAndDescriptionNotChangedIfAlreadySet()
    {
        $scheduler = (new Scheduler($this->getSqliteTestPersistence()))->createEntity();
        $scheduler->set('cronjob_class', SomeCronJob::class);
        $scheduler->set('name', 'someName');
        $scheduler->set('description', 'someDescription');
        $scheduler->save();
        self::assertSame(
            'someName',
            $scheduler->get('name')
        );
        self::assertSame(
            'someDescription',
            $scheduler->get('description')
        );
    }

    public function testNameAndDescriptionNotLoadedIfNoCronJobModelSet()
    {
        $scheduler = (new Scheduler($this->getSqliteTestPersistence()))->createEntity();
        $scheduler->save();
        self::assertNull($scheduler->get('name'));
        self::assertNull($scheduler->get('description'));
    }

}