<?php

declare(strict_types=1);

namespace cronforatk;

use Atk4\Data\Model;


class CronJobModel extends Model
{

    public $table = 'cron';

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

    protected function init(): void
    {
        parent::init();

        $this->addField(
            'cronjob_class',
            [
                'type' => 'string',
                'caption' => 'Diesen Cronjob ausführen',
                'values' => CronJobLoader::getAvailableCronJobs($this->cronFilesPaths),
                'ui' => ['form' => [Dropdown::class]]
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
                'ui' => ['form' => [Dropdown::class]]
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
                'ui' => ['form' => [Dropdown::class]]
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
                'ui' => ['form' => [Dropdown::class]]
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
            'last_executed',
            [
                'type' => 'datetime',
                'system' => true
            ]
        );

        $this->addField(
            'last_execution_success',
            [
                'type' => 'boolean',
                'system' => true
            ]
        );

        $this->addField(
            'last_execution_duration',
            [
                'type' => 'float',
                'system' => true
            ]
        );

        $this->addField(
            'last_execution_output',
            [
                'type' => 'json',
                'system' => true
            ]
        );

        //Name and Description can be set freely. If it is not set, use values from BaseCronJob instance
        $this->onHook(
            Model::HOOK_BEFORE_SAVE,
            function (self $cronJobEntity, bool $isUpdate) {
                if (!$cronJobEntity->isDirty('cronjob_class')) {
                    return;
                }

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