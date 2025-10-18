<?php declare(strict_types=1);

namespace PhilippR\Atk4\Cron;

use Atk4\Data\Model;


class Scheduler extends Model
{

    public $table = 'cron_scheduler';

    /** @var array<string, class-string>
     * path(es) to folders where cronjob php Files are located
     * format: path => namespace, e.g. 'src/data/cron' => 'YourProject\\Data\\Cron',
     */
    public array $cronFilesPaths = [];

    public const string INTERVAL_YEARLY = 'YEARLY';
    public const string INTERVAL_MONTHLY = 'MONTHLY';
    public const string INTERVAL_WEEKLY = 'WEEKLY';
    public const string INTERVAL_DAILY = 'DAILY';
    public const string INTERVAL_HOURLY = 'HOURLY';
    public const string INTERVAL_MINUTELY = 'MINUTELY';

    public static function getIntervals(): array
    {
        return [
            self::INTERVAL_YEARLY => 'Yearly',
            self::INTERVAL_MONTHLY => 'Monthly',
            self::INTERVAL_WEEKLY => 'Weekly',
            self::INTERVAL_DAILY => 'Daily',
            self::INTERVAL_HOURLY => 'Hourly',
            self::INTERVAL_MINUTELY => 'Minutely'
        ];
    }

    public const string MINUTELY_INTERVAL_EVERY_MINUTE = 'EVERY_MINUTE';
    public const string MINUTELY_INTERVAL_EVERY_FIFTH_MINUTE = 'EVERY_FIFTH_MINUTE';
    public const string MINUTELY_INTERVAL_EVERY_FIFTEENTH_MINUTE = 'EVERY_FIFTEENTH_MINUTE';

    public static function getMinutelyIntervalSettings(): array
    {
        return [
            self::MINUTELY_INTERVAL_EVERY_MINUTE => 'Every minute',
            self::MINUTELY_INTERVAL_EVERY_FIFTH_MINUTE => 'Every fifth minute',
            self::MINUTELY_INTERVAL_EVERY_FIFTEENTH_MINUTE => 'Every fifteenth minute'
        ];
    }

    public const string LOGGING_NO_LOGGING = 'NO_LOGGING';
    public const string LOGGING_ONLY_LOG_IF_OUTPUT = 'ONLY_LOG_IF_OUTPUT';
    public const string LOGGING_ALWAYS_LOG = 'ALWAYS_LOG';

    public static function getLoggingOptions(): array
    {
        return [
            self::LOGGING_NO_LOGGING => 'Do not log executions',
            self::LOGGING_ONLY_LOG_IF_OUTPUT => 'Only log executions if an execution creates output',
            self::LOGGING_ALWAYS_LOG => 'Always log executions'
        ];
    }

    protected function init(): void
    {
        parent::init();

        $this->addField(
            'cronjob_class',
            [
                'type' => 'string',
                'caption' => 'Execute this Cronjob',
                'values' => CronJobLoader::getAvailableCronJobs($this->cronFilesPaths),
            ]
        );

        $this->addField(
            'name',
            [
                'type' => 'string',
                'caption' => 'Cronjob Name'
            ]
        );

        $this->addField(
            'description',
            [
                'type' => 'text',
                'caption' => 'Description'
            ]
        );

        $this->addField(
            'defaults',
            [
                'type' => 'json',
                'caption' => 'Additional options for cronjob',
            ]
        );

        $this->addField(
            'is_active',
            [
                'type' => 'boolean',
                'caption' => 'Active'
            ]
        );

        $this->addField(
            'interval',
            [
                'type' => 'string',
                'caption' => 'Execution interval',
                'values' => self::getIntervals(),
            ]
        );

        $this->addField(
            'date_yearly',
            [
                'type' => 'date',
                'caption' => 'execute at date (year is ignored)',
            ]
        );

        $this->addField(
            'time_yearly',
            [
                'type' => 'time',
                'caption' => 'at this time',
            ]
        );

        $this->addField(
            'day_monthly',
            [
                'type' => 'integer',
                'caption' => 'day of month',
            ]
        );

        $this->addField(
            'time_monthly',
            [
                'type' => 'time',
                'caption' => 'at this time',
            ]
        );

        $this->addField(
            'weekday_weekly',
            [
                'type' => 'integer',
                'caption' => 'at this weekday',
            ]
        );

        $this->addField(
            'time_weekly',
            [
                'type' => 'time',
                'caption' => 'at this time',
            ]
        );

        $this->addField(
            'time_daily',
            [
                'type' => 'time',
                'caption' => 'Execute at this time',
            ]
        );

        $this->addField(
            'minute_hourly',
            [
                'type' => 'integer',
                'caption' => 'Execute at this minute (0-59)',
            ]
        );

        $this->addField(
            'interval_minutely',
            [
                'type' => 'string',
                'caption' => 'Intervall',
                'values' => self::getMinutelyIntervalSettings(),
            ]
        );

        $this->addField(
            'offset_minutely',
            [
                'type' => 'integer',
                'caption' => 'Shift for X Minutes (0-14)',
                'default' => 0,
            ]
        );

        $this->addField(
            'logging',
            [
                'type' => 'string',
                'values' => self::getLoggingOptions(),
                'default' => self::LOGGING_ONLY_LOG_IF_OUTPUT
            ]
        );

        $this->hasMany(
            ExecutionLog::class,
            ['model' => [ExecutionLog::class], 'theirField' => 'scheduler_id']
        );

        //Name and Description can be set freely. If it is not set, use values from BaseCronJob instance
        $this->onHook(
            Model::HOOK_BEFORE_SAVE,
            function (self $cronJobEntity) {
                if (!$cronJobEntity->isDirty('cronjob_class')) {
                    return;
                }
                /** @var class-string<BaseCronJob> $className */
                $className = $cronJobEntity->get('cronjob_class');
                if (empty($cronJobEntity->get('name'))) {
                    $cronJobEntity->set('name', $className::getName());
                }
                if (empty($cronJobEntity->get('description'))) {
                    $cronJobEntity->set('description', $className::getDescription());
                }
            }
        );
    }
}