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
        sleep(1);
    }
}