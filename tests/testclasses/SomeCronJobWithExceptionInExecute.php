<?php

declare(strict_types=1);

namespace cronforatk\tests\testclasses;

use cronforatk\BaseCronJob;
use Atk4\Data\Exception;

class SomeCronJobWithExceptionInExecute extends BaseCronJob
{

    public function execute(): void
    {
        throw new Exception('SomeException');
    }
}