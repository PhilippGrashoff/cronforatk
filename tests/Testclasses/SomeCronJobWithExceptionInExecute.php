<?php

declare(strict_types=1);

namespace PhilippR\Atk4\Cron\Tests\Testclasses;

use Atk4\Data\Exception;
use PhilippR\Atk4\Cron\BaseCronJob;

class SomeCronJobWithExceptionInExecute extends BaseCronJob
{

    public function execute(): void
    {
        throw new Exception('SomeException');
    }
}