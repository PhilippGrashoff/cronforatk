<?php

declare(strict_types=1);

namespace cronforatk;

use DirectoryIterator;
use ReflectionClass;


class CronJobLoader
{

    /**
     * Loads all available Cronjob implementations and returns them as array:
     * Fully\Qualified\ClassName => Name property
     *
     * @param array<string, class-string> $paths
     * @return array<class-string<BaseCronJob>,string>
     */
    public static function getAvailableCronJobs(array $paths): array
    {
        $availableCronJobs = [];
        foreach ($paths as $path => $namespace) {
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