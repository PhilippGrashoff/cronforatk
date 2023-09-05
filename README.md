# cronforatk
[![codecov](https://codecov.io/gh/PhilippGrashoff/cronforatk/branch/main/graph/badge.svg)](https://codecov.io/gh/PhilippGrashoff/cronforatk)

This repository is an extension for [atk4/data](https://github.com/atk4/data). It enables you to easily set and store the execution 
of many different cronjobs. Many different schedule options from yearly to minutely are available.
The logic of these cronjobs has to be implemented separately.

The repository consists of these classes:
* **BaseCronJob**: This is a base for all real cronjobs. The actual logic for a cronjob is implemented in a class extending BaseCronJob.
* **Scheduler**: This is used to persist each wanted execution. It contains the info which BaseCronJob child should be executed when.
* **Executor**: This class contains all the logic to decide when which Scheduler (and hence the corresponding BaseCronJob child) should be executed. **Executor::run() needs to be executed each minute** by a system cron for this repo to work properly.
* **ExecutionLog**: In here, execution results are logged. If execution results should be logged can be defined per Scheduler.
* **CronJobLoader**: This is a small helper. As all Cronjobs are stored as PHP classes, this class checks given directories for available BaseCronJob implementations.

# How to use
## Installation
The easiest way to use this repository is to add it to your composer.json in the require section:
```json
{
  "require": {
    "philippgrashoff/cronforatk": "4.0.*"
  }
}
```
## Setup Database
Two classes in this package need a database table: **Scheduler** and **ExecutionLog**
You can use [Atk4\Data\Schema\Migrator](https://github.com/atk4/data/blob/develop/src/Schema/Migrator.php) to create these 2 tables and the foreign key in your database.

```php
<?php

use cronforatk\Scheduler;
use cronforatk\ExecutionLog;
use Atk4\Data\Schema\Migrator;
use Atk4\Data\Persistence\Sql;

$persistence = new Sql(
  'connection_string',
  'username',
  'password'
);

$scheduler = new Scheduler($this->db);
(new Migrator($scheduler))->create();
$executionLog = new ExecutionLog($this->db);
(new Migrator($executionLog))->create()->createForeignKey($executionLog->getReference('scheduler_id'));
```

## Sample usage
### 1) Create a real cronjob that extends BaseCronJob
```php
class MyCronJob extends BaseCronJob
{

    public static string $name = 'SomeNameForThisCron';
    public static string $description = 'SomeDescriptionExplainingWhatThisIsDoing';

    public bool $strict = false;

    public function execute(): void
    {
        //do something
        $someModel = new SomeModel($this->persistence);
        $someModel->setLimit(100);
        foreach ($someModel as $entity) {
            if($this->strict) {
                $entity->doSomeStrictCheck();
            }
            else {
                $entity->doSomeOtherCheck();
            }
            //optionally add some output to log
            $this->executionLog[] = 'SomeModel With ID ' . $entity->getId()
                . 'checked at ' . (new \DateTime())->format(DATE_ATOM);
        }
    }
}
```

### 2) Add one or more Schedulers to schedule when the cronjob should be executed
Note: You can create a nice UI to create and update schedulers by using [atk4\ui](https://github.com/atk4/ui). In the following sample code, the schedulers are solely created on data level.
```php
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
```

### 3) Run Executor::run() minutely
Executor::run() checks which schedulers (and hence cronjobs) need to be executed at each minute by the definitions set in the schedulers. Add a simple script which is executed each minute by the system cron:
```php
$executor = new Executor($persistence);
$executor->run();
```

All sample code from this readme can be found in the `docs` directory.

# Versioning
The version numbers of this repository correspond with the atk4\data versions. So 4.0.x is compatible with atk4\data 4.0.x and so on.