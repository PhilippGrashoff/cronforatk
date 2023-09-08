<?php

declare(strict_types=1);

namespace PhilippR\Atk4\Cron\Tests;

use Atk4\Data\Persistence\Sql;
use Atk4\Data\Schema\TestCase;
use PhilippR\Atk4\Cron\ExecutionLog;
use PhilippR\Atk4\Cron\Scheduler;
use PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJob;

class SchedulerTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new Sql('sqlite::memory:');
        $this->createMigrator(new Scheduler($this->db))->create();
        $this->createMigrator(new ExecutionLog($this->db))->create();
    }

    public function testNameAndDescriptionLoadedFromBaseCronJob()
    {
        $scheduler = (new Scheduler($this->db))->createEntity();
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
        $scheduler = (new Scheduler($this->db))->createEntity();
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
        $scheduler = (new Scheduler($this->db))->createEntity();
        $scheduler->save();
        self::assertNull($scheduler->get('name'));
        self::assertNull($scheduler->get('description'));
    }

}