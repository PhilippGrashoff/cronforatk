<?php

declare(strict_types=1);

namespace cronforatk\tests\testclasses;

use Atk4\Data\Exception;
use cronforatk\BaseCronJob;

class SomeCronJobWithExceptionInExecute extends BaseCronJob
{

    public function execute(): void
    {
        throw new Exception('SomeException');
    }
}