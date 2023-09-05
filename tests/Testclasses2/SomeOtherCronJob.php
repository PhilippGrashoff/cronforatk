<?php

declare(strict_types=1);

namespace PhilippR\Atk4\Cron\Tests\Testclasses2;

use PhilippR\Atk4\Cron\BaseCronJob;


class SomeOtherCronJob extends BaseCronJob
{

    public static string $name = 'SomeNameForThisOtherCron';

    public function execute(): void
    {
        sleep(1);
    }
}