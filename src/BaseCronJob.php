<?php

declare(strict_types=1);

namespace cronforatk;

use atk4\core\DIContainerTrait;
use atk4\data\Exception;
use atk4\data\Persistence;
use JsonException;


/**
 * This class is meant as a Base to extend from for all Cronjobs.
 * Implement execute() in child cronjobs with all the logic inside.
 */
abstract class BaseCronJob
{

    use DIContainerTrait;

    /** @var string The name of the cronjob to display to a user */
    public string $name = '';

    /** @var string An optional description explaining what the cronjob is doing */
    public string $description = '';

    /** @var Persistence */
    public Persistence $persistence;


    /** @var array<int, string> In here, the cronjob can log what it did on execution */
    public array $executionLog = [];
    /**
     * @param Persistence $persistence
     * @param array<string, string> $defaults
     */
    public function __construct(Persistence $persistence, array $defaults = [])
    {
        $this->persistence = $persistence;
        $this->setDefaults($defaults);
    }

    /**
     * Implementation in descendants should throw exception on error
     * @return void
     * @throws Exception
     */
    public function execute(): void
    {
        throw new Exception('execute needs to ne implemented in descendants of ' . __CLASS__);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        if (empty($this->name)) {
            return (new \ReflectionClass($this))->getShortName();
        }
        return (string)$this->name;
    }

    /**
     * @return string
     * @throws JsonException
     */
    public function getExecutionLog(): string
    {
        return json_encode($this->executionLog, JSON_THROW_ON_ERROR);
    }
}