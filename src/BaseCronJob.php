<?php

declare(strict_types=1);

namespace cronforatk;

use atk4\core\DIContainerTrait;
use atk4\data\Exception;


/**
 * This class is meant as a Base to extend from for all Cronjobs.
 * Implement execute() in child cronjobs with all the Logic inside.
 */
abstract class BaseCronJob
{

    use DIContainerTrait;

    //The name of the cronjob to display to a user
    public $name = '';

    //some description explaining what the cron is doing
    public $description = '';

    //indicates if the cronjob was successful
    public $successful = false;


    public function __construct(array $defaults = [])
    {
        $this->setDefaults($defaults);
    }

    public function execute()
    {
        //make sure execute exists, otherwise throw exception
        throw new Exception('execute needs to ne implemented in descendants of ' . __CLASS__);
    }

    public function getName(): string
    {
        if (empty($this->name)) {
            return (new \ReflectionClass($this))->getShortName();
        }
        return (string)$this->name;
    }
}