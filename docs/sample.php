<?php declare(strict_types=1);

use Atk4\Data\Persistence\Sql;
use Atk4\Data\Schema\Migrator;
use PhilippR\Atk4\Cron\docs\MyCronJob;
use PhilippR\Atk4\Cron\ExecutionLog;
use PhilippR\Atk4\Cron\Executor;
use PhilippR\Atk4\Cron\Scheduler;

$persistence = new Sql(
    'connection_string',
    'username',
    'password'
);

//Setup database
$scheduler = new Scheduler($this->db);
(new Migrator($scheduler))->create();
$executionLog = new ExecutionLog($this->db);
(new Migrator($executionLog))->create()->createForeignKey($executionLog->getReference('scheduler_id'));

//tell the Schedulers where to look for Cronjob implementations.
//In real implementations, extend Scheduler to set this once for all Schedulers
$pathsToCronJobs = [__DIR__ => 'cronforatk\docs'];

//Have our Cronjob executed Daily.
$schedulerDaily = new Scheduler($persistence, ['cronFilesPaths' => $pathsToCronJobs]);
$schedulerDaily->set('cronjob_class', MyCronJob::class);
$schedulerDaily->set('interval', 'DAILY');
$schedulerDaily->set('time_daily', '03:45');
$schedulerDaily->save();

//you could add more schedulers executing the same cronjob in different intervals.
// Here, we will add a weekly check sets the "strict" of MyCronJob to true. Like this, cronjobs can be parametrized
$schedulerWeekly = new Scheduler($persistence, ['cronFilesPaths' => $pathsToCronJobs]);
$schedulerWeekly->set('cronjob_class', MyCronJob::class);
$schedulerWeekly->set('interval', 'WEEKLY');
$schedulerWeekly->set('weekday_weekly', 6); //Saturday
$schedulerWeekly->set('time_weekly', '01:32');
$schedulerWeekly->set('defaults', ['strict' => true]);
$schedulerWeekly->save();

//Now you just need a single executor. Add a separate Script that is executed minutely by your system. Executor::run() then
//checks which schedulers (and hence cronjobs) need to be executed.
$executor = new Executor($persistence);
$executor->run();