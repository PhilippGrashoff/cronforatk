<?php

declare(strict_types=1);

namespace PhilippR\Atk4\Cron\Tests;

use Atk4\Data\Persistence\Sql;
use Atk4\Data\Schema\TestCase;
use PhilippR\Atk4\Cron\ExecutionLog;
use PhilippR\Atk4\Cron\Executor;
use PhilippR\Atk4\Cron\Scheduler;
use PhilippR\Atk4\Cron\Tests\Testclasses\SomeCronJobWithExceptionInExecute;
use PhilippR\Atk4\Cron\Tests\Testclasses2\SomeOtherCronJob;

class ExecutionLogTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new Sql('sqlite::memory:');
        $this->createMigrator(new Scheduler($this->db))->create();
        $this->createMigrator(new ExecutionLog($this->db))->create();
    }
    public function testDurationIsLogged()
    {
        $persistence = $this->db;
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);

        $scheduler1 = ExecutorTest::getScheduler(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
                'cronjob_class' => SomeOtherCronJob::class,
                'logging' => 'ALWAYS_LOG'
            ]
        );

        $executor = new Executor($persistence);
        $executor->run($testTime);

        $scheduler1->reload();

        self::assertEqualsWithDelta(
            1.0,
            ExecutorTest::getLastExecutionLog($scheduler1)->get('execution_duration'),
            0.05
        );
    }

    public function testExecutionSuccessIsLogged(): void
    {
        $persistence = $this->db;
        $testTime = new \DateTime('2020-09-07');
        $testTime->setTime(3, 3);

        $scheduler1 = ExecutorTest::getScheduler(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );


        $scheduler2 = ExecutorTest::getScheduler(
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
            ExecutorTest::getLastExecutionLog($scheduler1)->get('execution_successful')
        );
        self::assertFalse(
            ExecutorTest::getLastExecutionLog($scheduler2)->get('execution_successful')
        );
    }

    public function testLoggingOptionNoLogging(): void
    {
        $persistence = $this->db;
        $testTime = new \DateTime('2020-09-07');
        $testTime->setTime(3, 3);

        $scheduler1 = ExecutorTest::getScheduler(
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
        $persistence = $this->db;
        $testTime = new \DateTime('2020-09-07');
        $testTime->setTime(3, 3);

        $scheduler1 = ExecutorTest::getScheduler(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );
        //does not produce any output, should not produce log
        $scheduler2 = ExecutorTest::getScheduler(
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
        $persistence = $this->db;
        $testTime = new \DateTime('2020-09-07');
        $testTime->setTime(3, 3);

        $scheduler1 = ExecutorTest::getScheduler(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );
        //does not produce any output, should not produce log
        $scheduler2 = ExecutorTest::getScheduler(
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

    public function testLastExecutedSaved(): void
    {
        $persistence = $this->db;
        $dateTime = new \DateTime();
        //this one should be executed
        $entity = ExecutorTest::getScheduler(
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
            ExecutorTest::getLastExecutionLog($entity)->get('execution_datetime')->format('d-m-Y H:i:s')
        );
    }

}