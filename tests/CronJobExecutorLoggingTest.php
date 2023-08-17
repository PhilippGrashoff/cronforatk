<?php

declare(strict_types=1);


use Atk4\Data\Persistence;
use atkextendedtestcase\TestCase;
use cronforatk\CronJobExecutionLog;
use cronforatk\CronJobExecutor;
use cronforatk\CronJobModel;
use cronforatk\tests\testclasses\SomeCronJob;
use cronforatk\tests\testclasses\SomeCronJobWithExceptionInExecute;
use cronforatk\tests\testclasses2\SomeOtherCronJob;

class CronJobExecutorLoggingTest extends TestCase
{

    protected array $sqlitePersistenceModels = [
        CronJobModel::class,
        CronJobExecutionLog::class
    ];

    public function testDurationIsLogged()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-05-05');
        $testTime->setTime(3, 3);

        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );

        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        $cronJobModel1->reload();

        self::assertEqualsWithDelta(
            1.0,
            $this->getLastExecutionLog($cronJobModel1)->get('execution_duration'),
            0.05
        );
    }

    public function testExecutionSuccessIsLogged(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-09-07');
        $testTime->setTime(3, 3);

        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );


        $cronJobModel2 = $this->_getRecord(
            $persistence,
            [
                'cronjob_class' => SomeCronJobWithExceptionInExecute::class,
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
                'logging' => 'ALWAYS_LOG'
            ]
        );

        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        self::assertTrue(
            $this->getLastExecutionLog($cronJobModel1)->get('execution_successful')
        );
        self::assertFalse(
            $this->getLastExecutionLog($cronJobModel2)->get('execution_successful')
        );
    }

    public function testLoggingOptionNoLogging(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-09-07');
        $testTime->setTime(3, 3);

        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
                'logging' => 'NO_LOGGING'
            ]
        );

        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        self::assertSame(
            0,
            (int)(new CronJobExecutionLog($persistence))->action('count')->getOne()
        );
    }

    public function testLoggingOptionOnlyIfLogOutput(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-09-07');
        $testTime->setTime(3, 3);

        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );
        //does not produce any output, should not produce log
        $cronJobModel2 = $this->_getRecord(
            $persistence,
            [
                'cronjob_class' => SomeOtherCronJob::class,
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );

        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        self::assertSame(
            1,
            (int)(new CronJobExecutionLog($persistence))->action('count')->getOne()
        );
        self::assertSame(
            1,
            (int)$cronJobModel1->ref(CronJobExecutionLog::class)->action('count')->getOne()
        );
        self::assertSame(
            0,
            (int)$cronJobModel2->ref(CronJobExecutionLog::class)->action('count')->getOne()
        );
    }

    public function testLoggingOptionAlwaysLog(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testTime = new \DateTime('2020-09-07');
        $testTime->setTime(3, 3);

        $cronJobModel1 = $this->_getRecord(
            $persistence,
            [
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
            ]
        );
        //does not produce any output, should not produce log
        $cronJobModel2 = $this->_getRecord(
            $persistence,
            [
                'cronjob_class' => SomeOtherCronJob::class,
                'interval' => 'YEARLY',
                'date_yearly' => $testTime,
                'time_yearly' => $testTime,
                'logging' => 'ALWAYS_LOG'
            ]
        );

        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run($testTime);

        self::assertSame(
            2,
            (int)(new CronJobExecutionLog($persistence))->action('count')->getOne()
        );
        self::assertSame(
            1,
            (int)$cronJobModel1->ref(CronJobExecutionLog::class)->action('count')->getOne()
        );
        self::assertSame(
            1,
            (int)$cronJobModel2->ref(CronJobExecutionLog::class)->action('count')->getOne()
        );
    }

    public function testLastExecutedSaved()
    {
        $persistence = $this->getSqliteTestPersistence();
        $dateTime = new \DateTime();
        //this one should be executed
        $entity = $this->_getRecord(
            $persistence,
            [
                'interval' => 'MINUTELY',
                'interval_minutely' => 'EVERY_MINUTE',
            ]
        );

        $cronJobExecutor = new CronJobExecutor($persistence);
        $cronJobExecutor->run();

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

    private function _getRecord(Persistence $persistence, array $set = []): CronJobModel
    {
        $entity = (new CronJobModel($persistence))->createEntity();

        $entity->set('cronjob_class', SomeCronJob::class);
        $entity->set('is_active', 1);
        $entity->setMulti($set);

        $entity->save();

        return $entity;
    }

    private function getLastExecutionLog(CronJobModel $cronJobModel): CronJobExecutionLog
    {
        $executionLog = $cronJobModel->ref(CronJobExecutionLog::class);
        $executionLog = $executionLog->loadAny();

        return $executionLog;
    }
}