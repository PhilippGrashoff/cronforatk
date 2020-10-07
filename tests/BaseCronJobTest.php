<?php

declare(strict_types=1);

namespace cronforatk\tests;

use atk4\data\Exception;
use atk4\data\Persistence;
use cronforatk\tests\testclasses\SomeCronJob;
use cronforatk\tests\testclasses\SomeCronJobWithoutExecute;
use traitsforatkdata\TestCase;

class BaseCronJobTest extends TestCase {

    public function testConstruct() {
        $persistence = $this->getSqliteTestPersistence();
        $cron = new SomeCronJob($persistence, ['name' => 'SOMENAME']);
        self::assertSame(
            $persistence,
            $cron->persistence
        );

        self::assertSame(
            'SOMENAME',
            $cron->name
        );
    }

    public function testExceptionNoExecuteImplementedInDescendant() {
        $persistence = $this->getSqliteTestPersistence();
        $cron = new SomeCronJobWithoutExecute($persistence);
        self::expectException(Exception::class);
        $cron->execute();
    }

    public function testGetName() {
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
