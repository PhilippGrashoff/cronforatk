<?php

declare(strict_types=1);

namespace cronforatk\tests\testclasses;

use cronforatk\BaseCronJob;
use atk4\data\Exception;

class SomeCronJobWithExceptionInExecute extends BaseCronJob
{

    public function execute(): void
    {
        throw new Exception('SomeException');
    }
}