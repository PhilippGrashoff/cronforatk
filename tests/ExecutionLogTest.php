<?php

declare(strict_types=1);

namespace cronforatk\tests;

use Atk4\Data\Persistence;
use atkextendedtestcase\TestCase;
use cronforatk\ExecutionLog;
use cronforatk\Executor;
use cronforatk\Scheduler;
use cronforatk\tests\testclasses\SomeCronJob;
use cronforatk\tests\testclasses\SomeCronJobWithExceptionInExecute;
use cronforatk\tests\testclasses2\SomeOtherCronJob;

class ExecutionLogTest extends TestCase
{

    protected array $sqlitePersistenceModels = [
        Scheduler::class,
        ExecutionLog::class
    ];

    public function testDurationIsLogged()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);

        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );

        $executor = new Executor($persistence);
        $executor->run($testTime);

        $scheduler1->reload();

        self::assertEqualsWithDelta(
            1.0,
            $this->getLastExecutionLog($scheduler1)->get('execution_duration'),
            0.05
        );
    }

    public function testExecutionSuccessIsLogged(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-09-07');
        $testTime->setTime(3, 3);

        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );


        $scheduler2 = $this->_getScheduler(
            $persistence,
            [
                'cronjob_class' => SomeCronJobWithExceptionInExecute::class,
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
                'logging' => 'ALWAYS_LOG'
            ]
        );

        $executor = new Executor($persistence);
        $executor->run($testTime);

        self::assertTrue(
            $this->getLastExecutionLog($scheduler1)->get('execution_successful')
        );
        self::assertFalse(
            $this->getLastExecutionLog($scheduler2)->get('execution_successful')
        );
    }

    public function testLoggingOptionNoLogging(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-09-07');
        $testTime->setTime(3, 3);

        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
                'logging' => 'NO_LOGGING'
            ]
        );

        $executor = new Executor($persistence);
        $executor->run($testTime);

        self::assertSame(
            0,
            (int)(new ExecutionLog($persistence))->action('count')->getOne()
        );
    }

    public function testLoggingOptionOnlyIfLogOutput(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-09-07');
        $testTime->setTime(3, 3);

        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );
        //does not produce any output, should not produce log
        $scheduler2 = $this->_getScheduler(
            $persistence,
            [
                'cronjob_class' => SomeOtherCronJob::class,
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );

        $executor = new Executor($persistence);
        $executor->run($testTime);

        self::assertSame(
            1,
            (int)(new ExecutionLog($persistence))->action('count')->getOne()
        );
        self::assertSame(
            1,
            (int)$scheduler1->ref(ExecutionLog::class)->action('count')->getOne()
        );
        self::assertSame(
            0,
            (int)$scheduler2->ref(ExecutionLog::class)->action('count')->getOne()
        );
    }

    public function testLoggingOptionAlwaysLog(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-09-07');
        $testTime->setTime(3, 3);

        $scheduler1 = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );
        //does not produce any output, should not produce log
        $scheduler2 = $this->_getScheduler(
            $persistence,
            [
                'cronjob_class' => SomeOtherCronJob::class,
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
                'logging' => 'ALWAYS_LOG'
            ]
        );

        $executor = new Executor($persistence);
        $executor->run($testTime);

        self::assertSame(
            2,
            (int)(new ExecutionLog($persistence))->action('count')->getOne()
        );
        self::assertSame(
            1,
            (int)$scheduler1->ref(ExecutionLog::class)->action('count')->getOne()
        );
        self::assertSame(
            1,
            (int)$scheduler2->ref(ExecutionLog::class)->action('count')->getOne()
        );
    }

    public function testLastExecutedSaved()
    {
        $persistence = $this->getSqliteTestPersistence();
        $dateTime = new \DateTime();
        //this one should be executed
        $entity = $this->_getScheduler(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_MINUTE',
            ]
        );

        $executor = new Executor($persistence);
        $executor->run();

        $entity->reload();

        self::assertSame(
            $dateTime->format('d-m-Y H:i:s'),
            $entity->get('last_executed')->format('d-m-Y H:i:s')
        );
        self::assertSame(
            $dateTime->format('d-m-Y H:i:s'),
            $this->getLastExecutionLog($entity)->get('execution_datetime')->format('d-m-Y H:i:s')
        );
    }

    private function _getScheduler(Persistence $persistence, array $set = []): Scheduler
    {
        $entity = (new Scheduler($persistence))->createEntity();

        $entity->set('cronjob_class', SomeCronJob::class);
        $entity->set('is_active', 1);
        $entity->setMulti($set);

        $entity->save();

        return $entity;
    }

    private function getLastExecutionLog(Scheduler $scheduler): ExecutionLog
    {
        $executionLog = $scheduler->ref(ExecutionLog::class);
        $executionLog = $executionLog->loadAny();

        return $executionLog;
    }
}