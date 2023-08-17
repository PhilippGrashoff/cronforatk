<?php

declare(strict_types=1);


use atkextendedtestcase\TestCase;
use cronforatk\CronJobModel;
use cronforatk\tests\testclasses\SomeCronJob;

class CronJobModelTest extends TestCase
{

    protected array $sqlitePersistenceModels = [
        CronJobModel::class,
        \cronforatk\CronJobExecutionLog::class
    ];

    public function testNameAndDescriptionLoadedFromBaseCronJob()
    {
        $cronJobModel = (new CronJobModel($this->getSqliteTestPersistence()))->createEntity();
        $cronJobModel->set('cronjob_class', SomeCronJob::class);
        $cronJobModel->save();
        self::assertSame(
            'SomeNameForThisCron',
            $cronJobModel->get('name')
        );
        self::assertSame(
            'SomeDescriptionExplainingWhatThisIsDoing',
            $cronJobModel->get('description')
        );
    }

    public function testNameAndDescriptionNotChangedIfAlreadySet()
    {
        $cronJobModel = (new CronJobModel($this->getSqliteTestPersistence()))->createEntity();
        $cronJobModel->set('cronjob_class', SomeCronJob::class);
        $cronJobModel->set('name', 'someName');
        $cronJobModel->set('description', 'someDescription');
        $cronJobModel->save();
        self::assertSame(
            'someName',
            $cronJobModel->get('name')
        );
        self::assertSame(
            'someDescription',
            $cronJobModel->get('description')
        );
    }

    public function testNameAndDescriptionNotLoadedIfNoCronJobModelSet()
    {
        $cronJobModel = (new CronJobModel($this->getSqliteTestPersistence()))->createEntity();
        $cronJobModel->save();
        self::assertNull($cronJobModel->get('name'));
        self::assertNull($cronJobModel->get('description'));
    }

}