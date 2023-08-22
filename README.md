# cronforatk
[![codecov](https://codecov.io/gh/PhilippGrashoff/cronforatk/branch/main/graph/badge.svg)](https://codecov.io/gh/PhilippGrashoff/cronforatk)

This repository is an extension for atk4/data. It enables you to easily set and store the execution 
of many different cronjobs. Many different schedule options from yearly to minutely are available.
The logic of these cronjobs has to be implemented separately.

The repository consists of these classes:
* BaseCronJob: This is a base for all real cronjobs. The actual logic for a cronjob is implemented in a class extending BaseCronJob.
* Scheduler: This is used to persist each wanted execution. It contains the info which BaseCronJob child should be executed when.
* Executor: This class contains all the logic to decide when which Scheduler (and hence the corresponding BaseCronJob child) should be executed. **Executor::run() needs to be executed each minute** by a system cron for this repo to work properly.
* ExecutionLog: In here, execution results are logged. If execution results should be logged can be defined per Scheduler.
* CronJobLoader: This is a small helper. As all Cronjobs are stored as PHP classes, this class checks given directories for available BaseCronJob implementations.
