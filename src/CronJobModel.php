<?php

declare(strict_types=1);

namespace cronforatk;

use atk4\data\Model;
use atk4\ui\Form\Control\Dropdown;


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
        'YEARLY',
        'MONTHLY',
        'WEEKLY',
        'DAILY',
        'HOURLY',
        'MINUTELY'
    ];

    /** @var array|string[] */
    public static array $minutelyIntervalSettings = [
        'EVERY_MINUTE',
        'EVERY_FIFTH_MINUTE',
        'EVERY_FIFTEENTH_MINUTE'
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
                'type' => 'array',
                'caption' => 'Additional options for cronjob',
                'serialize' => 'json'
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
                'type' => 'array',
                'serialize' => 'serialize',
                'system' => true
            ]
        );


        $this->addCalculatedField(
            'schedule_info',
            [
                function (self $record): string {
                    return $record->getScheduleInfo();
                },
                'type' => 'string',
                'caption' => 'Ausführungsintervall',
            ]
        );

        $this->onHook(
            Model::HOOK_BEFORE_SAVE,
            function (self $cronJobEntity, bool $isUpdate) {
                if (!$cronJobEntity->isDirty('cronjob_class')) {
                    return;
                }

                $className = $cronJobEntity->get('cronjob_class');

                $cronJobEntity->set('name', $className::getName());
                $cronJobEntity->set('description', $className::getDescription());
            }
        );
    }

    /**
     * @return string
     */
    public function getScheduleInfo(): string
    {
        if (!$this->get('is_active')) {
            return '';
        }
        if (
            $this->get('interval') == 'YEARLY'
            && $this->get('date_yearly')
            && $this->get('time_yearly')
        ) {
            return 'Jährlich am ' . $this->get('date_yearly')->format('m.Y')
                . ' um ' . $this->get('time_yearly')->format('H:i');
        }
        if (
            $this->get('interval') == 'MONTHLY'
            && $this->get('day_monthly')
            && $this->get('time_monthly')
        ) {
            return 'Monatlich am ' . $this->get('day_monthly')
                . '. um ' . $this->get('time_monthly')->format('H:i');
        }
        if (
            $this->get('interval') == 'WEEKLY'
            && $this->get('weekday_weekly')
            && $this->get('time_weekly')
        ) {
            return 'Wöchentlich am ' . GERMAN_WEEKDAYS[$this->get('weekday_weekly')]
                . ' um ' . $this->get('time_weekly')->format('H:i');
        }
        if (
            $this->get('interval') == 'DAILY'
            && $this->get('time_daily')
        ) {
            return 'Täglich um ' . $this->get('time_daily')->format('H:i');
        }
        if (
            $this->get('interval') == 'HOURLY'
            && $this->get('minute_hourly')
        ) {
            return 'Stündlich zur ' . $this->get('minute_hourly') . '. Minute';
        }
        if (
            $this->get('interval') == 'MINUTELY'
            && $this->get('interval_minutely')
        ) {
            if ($this->get('interval_minutely') == 'EVERY_MINUTE') {
                return 'Zu jeder Minute';
            } elseif ($this->get('interval_minutely') == 'EVERY_FIFTH_MINUTE') {
                return '5-Minütig um ' . (0 + $this->get('offset_minutely'))
                    . ', ' . (5 + $this->get(
                            'offset_minutely'
                        )) . ', ...';
            } elseif ($this->get('interval_minutely') == 'EVERY_FIFTEENTH_MINUTE') {
                return 'Viertelstündlich um ' . (0 + $this->get('offset_minutely'))
                    . ', ' . (15 + $this->get('offset_minutely')) . ', ...';
            }
        }

        return '';
    }
}