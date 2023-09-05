<?php

declare(strict_types=1);

namespace cronforatk\tests\testclasses;

use cronforatk\BaseCronJob;


class SomeCronJob extends BaseCronJob
{

    public static string $name = 'SomeNameForThisCron';
    public static string $description = 'SomeDescriptionExplainingWhatThisIsDoing';

    public function execute(): void
    {
        //dummy output for tests, here as string
        $this->executionLog[] = 'SomeModel With ID=3 deleted';
        //dummy log as stdclass; more info can be added here
        $stdClassLog = new \stdClass();
        $stdClassLog->message = 'SomeModel deleted';
        $stdClassLog->id = 35;
        $stdClassLog->name = 'SomeName';
        $this->executionLog[] = $stdClassLog;
    }
}