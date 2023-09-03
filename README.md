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
You can use [Atk4\Data\Schema\Migrator](https://github.com/atk4/data/blob/develop/src/Schema/Migrator.php) to create the tables in your database. You will have to add the foreign key `scheduler_id` to the `execution_log` table manually.

Here is a sample query for MySQL:
```sql
CREATE TABLE `execution_log` (
  `id` int UNSIGNED NOT NULL,
  `execution_datetime` datetime DEFAULT NULL,
  `execution_successful` tinyint(1) DEFAULT NULL,
  `execution_duration` double DEFAULT NULL,
  `execution_output` json DEFAULT NULL,
  `scheduler_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `scheduler` (
  `id` int UNSIGNED NOT NULL,
  `cronjob_class` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `defaults` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `interval` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_yearly` date DEFAULT NULL,
  `time_yearly` time DEFAULT NULL,
  `day_monthly` int DEFAULT NULL,
  `time_monthly` time DEFAULT NULL,
  `weekday_weekly` int DEFAULT NULL,
  `time_weekly` time DEFAULT NULL,
  `time_daily` time DEFAULT NULL,
  `minute_hourly` int DEFAULT NULL,
  `interval_minutely` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offset_minutely` int DEFAULT NULL,
  `logging` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `execution_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_scheduler_id` (`scheduler_id`);

ALTER TABLE `scheduler`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `execution_log`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `scheduler`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `execution_log`
  ADD CONSTRAINT `fk_scheduler_id` FOREIGN KEY (`scheduler_id`) REFERENCES `scheduler` (`id`);
```

## Sample usage
### 1) Create a real cronjob that extends BaseCronJob
```php

use cronforatk\BaseCronJob;

class MyCronJob extends BaseCronJob
{

    public static string $name = 'SomeNameForThisCron';
    public static string $description = 'SomeDescriptionExplainingWhatThisIsDoing';

    public function execute(): void
    {
        //do something
        $someModel = new SomeModel($this->persistence);
        $someModel->setLimit(100);
        foreach($someModel as $entity) {
            $entity->doSomeCheck();    
            //optionally add some output
            $this->executionLog[] = 'SomeModel With ID ' . $entity->getId() . 'checked at ' . (new \DateTime())->format(DATE_ATOM);
        }
    }
}
```
### 2) Add one or more Schedulers to schedule when the cronjob should be executed
```php

```
Hint to use atk4\ui to easily create and set execution intervals for schedulers.

# Versioning
The version numbers of this repository correspond with the atk4\data versions. So 4.0.x is compatible with atk4\data 4.0.x and so on.