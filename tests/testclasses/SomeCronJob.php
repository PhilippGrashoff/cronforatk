<?php

declare(strict_types=1);

namespace cronforatk\tests\testclasses;

use cronforatk\BaseCronJob;


class SomeCronJob extends BaseCronJob
{

    public $description = 'SomeDescriptionExplainingWhatThisIsDoing';

    public function execute(): void
    {
        sleep(1);
    }
}