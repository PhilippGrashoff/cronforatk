<?php

declare(strict_types=1);

namespace PhilippR\Atk4\Cron\Tests\Testclasses;

use PhilippR\Atk4\Cron\BaseCronJob;

class SomeCronJob extends BaseCronJob
{

    public static string $name = 'SomeNameForThisCron';
    public static string $description = 'SomeDescriptionExplainingWhatThisIsDoing';

    public function execute(): void
    {
        //dummy output for tests, here as string
        $this->executionLog[] = 'SomeModel With ID=3 deleted';
    }
}