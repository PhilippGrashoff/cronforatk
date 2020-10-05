<?php

declare(strict_types=1);

namespace cronforatk\tests;

use auditforatk\Audit;
use PMRAtk\App\App;
use PMRAtk\Data\Cron\BaseCronJob;
use PMRAtk\Data\Email\BaseEmail;
use PMRAtk\Data\Email\EmailAccount;
use PMRAtk\Data\Email\EmailTemplate;
use PMRAtk\tests\phpunit\TestCase;
use PMRAtk\tests\TestClasses\CronTestClasses\SampleCron;
use PMRAtk\tests\TestClasses\CronTestClasses\SampleCronWithEmailMessage;
use PMRAtk\tests\TestClasses\CronTestClasses\SampleCronWithException;
use PMRAtk\tests\TestClasses\CronTestClasses\SampleCronWithoutExecuteImplemented;

class BaseCronJobTest extends TestCase {

    protected $sqlitePersistenceModels = [
        BaseEmail::class,
        EmailAccount::class,
        Audit::class,
        EmailTemplate::class
    ];

    public function testSuccessfulCronJob() {
        $persistence = $this->getSqliteTestPersistence();
        $app = new App(['nologin'], ['db' => $persistence, 'always_run' => false]);
        $this->_addStandardEmailAccount($persistence);
        $c = new SampleCronWithEmailMessage($app, ['addAdminToSuccessEmail' => true]);
        $c->execute();
        self::assertTrue($c->successful);
        self::assertEquals(1, count($c->app->userMessages));
    }

    public function testExceptionCronJob() {
        $this->_addStandardEmailAccount();
        $c = new SampleCronWithException(self::$app, ['addAdminToSuccessEmail' => true]);
        $c->execute();
        //an email was sent
        self::assertFalse($c->successful);
    }

    public function testExceptionNoExecuteImplemented() {
        $this->_addStandardEmailAccount();
        self::expectException(\atk4\data\Exception::class);
        $c = new SampleCronWithoutExecuteImplemented(self::$app, ['addAdminToSuccessEmail' => true]);
        $c->execute();
    }

    public function testNoEmailOnNoSuccessMessage() {
        $this->_addStandardEmailAccount();
        self::$app->userMessages = [];
        $c = new SampleCron(self::$app);
        $c->execute();
        self::assertTrue($c->successful);
        self::assertTrue(empty($c->phpMailer->getLastMessageID()));
    }

    public function testNoRecipientNoSuccessMessage() {
        $this->_addStandardEmailAccount();
        self::$app->userMessages[] = ['message' => 'Duggu', 'class' => 'error'];
        $c = new SampleCron(self::$app);
        $c->execute();
        self::assertTrue($c->successful);
        self::assertTrue(empty($c->phpMailer->getLastMessageID()));
    }

    public function testGetName() {
        $this->_addStandardEmailAccount();
        $c = new SampleCronWithEmailMessage(self::$app);
        self::assertEquals('TestName', $c->getName());
        $c = new SampleCron(self::$app);
        self::assertEquals('SomeTestCron', $c->getName());
    }
}
