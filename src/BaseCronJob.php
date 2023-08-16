<?php

declare(strict_types=1);

namespace cronforatk;

use Atk4\Core\DiContainerTrait;
use Atk4\Data\Exception;
use Atk4\Data\Persistence;


/**
 * This class is meant as a Base to extend from for all Cronjobs.
 * Implement execute() in child cronjobs with all the logic inside.
 */
abstract class BaseCronJob
{

    use DIContainerTrait;

    /** @var string
     * The name of the cronjob to display to a user. Static so it can be accessed by CronJobLoader
     * without having to create an instance
     */
    protected static string $name = '';

    /** @var string An optional description explaining what the cronjob is doing */
    protected static string $description = '';

    /** @var Persistence */
    protected Persistence $persistence;

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
    public static function getName(): string
    {
        if (empty(static::$name)) {
            return (new \ReflectionClass(static::class))->getShortName();
        }
        return static::$name;
    }

    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return static::$description;
    }
}