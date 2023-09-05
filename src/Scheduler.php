<?php

declare(strict_types=1);

namespace cronforatk;

use Atk4\Data\Model;


class Scheduler extends Model
{

    public $table = 'scheduler';

    /** @var array<string, class-string>
     * path(es) to  folders where  Cronjob php Files are located
     * format: path => namespace, e.g. 'src/data/cron' => 'YourProject\\Data\\Cron',
     */
    public array $cronFilesPaths = [];

    /** @var array|string[] */
    public static array $intervalSettings = [
        'YEARLY' => 'Yearly',
        'MONTHLY' => 'Monthly',
        'WEEKLY' => 'Weekly',
        'DAILY' => 'Daily',
        'HOURLY' => 'Hourly',
        'MINUTELY' => 'Minutely'
    ];

    /** @var array|string[] */
    public static array $minutelyIntervalSettings = [
        'EVERY_MINUTE' => 'Every minute',
        'EVERY_FIFTH_MINUTE' => 'Every fifth minute',
        'EVERY_FIFTEENTH_MINUTE' => 'Every fifteenth minute'
    ];

    /** @var array|string[] */
    public static array $loggingOptions = [
        'NO_LOGGING' => 'Do not log executions',
        'ONLY_LOG_IF_OUTPUT' => 'Only log executions if an execution creates output',
        'ALWAYS_LOG' => 'Always log executions'
    ];

    protected function init(): void
    {
        parent::init();

        $this->addField(
            'cronjob_class',
            [
                'type' => 'string',
                'caption' => 'Diesen Cronjob ausführen',
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
                'values' => self::$intervalSettings,
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
                'values' => self::$minutelyIntervalSettings,
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
                'values' => self::$loggingOptions,
                'default' => 'ONLY_LOG_IF_OUTPUT'
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