<?php declare(strict_types=1);

namespace PhilippR\Atk4\Cron;

use Atk4\Core\DiContainerTrait;
use Atk4\Data\Persistence;
use ReflectionClass;
use stdClass;

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

    /** @var array<int, string|stdClass> In here, the cronjob can log what it did on execution */
    protected array $executionLog = [];

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
     * Needs to be implemented in descendants of BaseCronJob. It is designed to throw exceptions if errors
     * @return void
     */
    abstract public function execute(): void;

    /**
     * @return string
     */
    public static function getName(): string
    {
        if (empty(static::$name)) {
            return (new ReflectionClass(static::class))->getShortName();
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

    /**
     * @return stdClass[]|string[]
     */
    public function getExecutionLog(): array
    {
        return $this->executionLog;
    }
}