<?php

declare(strict_types=1);

namespace cronforatk\tests\testclasses2;

use cronforatk\BaseCronJob;


class SomeOtherCronJob extends BaseCronJob
{

    public static string $name = 'SomeNameForThisOtherCron';

    public function execute(): void
    {
        sleep(1);
    }
}