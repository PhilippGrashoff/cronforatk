<?php

declare(strict_types=1);

namespace cronforatk;

use DirectoryIterator;
use ReflectionClass;


class CronJobLoader
{

    /** @var array<string, string>
     * path(es) to  folders where  Cronjob php Files are located
     * format: path => namespace, e.g. 'src/data/cron' => 'YourProject\\Data\\Cron',
     */
    public array $cronFilesPath = [];


    /**
     * Loads all Cronjob Files and returns them as array:
     * Fully\Qualified\ClassName => Name property
     *
     * @return array<class-string,string>
     */
    public function getAvailableCronJobs(): array
    {
        $availableCronJobs = [];
        foreach ($this->cronFilesPath as $path => $namespace) {
            $dirName = FILE_BASE_PATH . $path;
            if (!file_exists($dirName)) {
                continue;
            }

            foreach (new DirectoryIterator($dirName) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $className = $namespace . '\\' . $file->getBasename('.php');
                if (
                    !class_exists($className)
                    || (new ReflectionClass($className))->isAbstract()
                    || !(new ReflectionClass($className))->isSubclassOf(BaseCronJob::class)
                ) {
                    continue;
                }

                $availableCronJobs[$className] = $className::getName();
            }
        }

        return $availableCronJobs;
    }
}