<?php

declare(strict_types=1);

namespace cronforatk;

use Atk4\Data\Exception;
use Atk4\Data\Model;
use atk4\ui\Form\Control\Dropdown;
use DirectoryIterator;
use ReflectionClass;


class CronExecutor extends Model
{

    public $table = 'cron';

    public $intervalSettings = [
        'YEARLY' => 'Jährlich',
        'MONTHLY' => 'Monatlich',
        'WEEKLY' => 'Wöchentlich',
        'DAILY' => 'Täglich',
        'HOURLY' => 'Stündlich',
        'MINUTELY' => 'Minütlich'
    ];

    public $minutelyIntervalSettings = [
        'EVERY_MINUTE' => 'Jede Minute',
        'EVERY_FIFTH_MINUTE' => 'Alle 5 Minuten',
        'EVERY_FIFTEENTH_MINUTE' => 'Alle 15 Minuten'
    ];

    //path(es) to  folders where  Cronjob php Files are located
    //format: path => namespace, e.g. 'src/data/cron' => 'YourProject\\Data\\Cron',
    public $cronFilesPath = [];

    public $currentDate;
    public $currentWeekday;
    public $currentDay;
    public $currentTime;
    public $currentMinute;


    protected function init(): void
    {
        parent::init();
        $this->addFields(
            [
                [
                    'type',
                    'type' => 'string',
                    'caption' => 'Diesen Cronjob ausführen',
                    'values' => $this->getAvailableCrons(),
                    'ui' => ['form' => [Dropdown::class]]
                ],
                [
                    'name',
                    'type' => 'string',
                    'caption' => 'Bezeichung'
                ],
                [
                    'description',
                    'type' => 'text',
                    'caption' => 'Beschreibung'
                ],
                [
                    'defaults',
                    'type' => 'array',
                    'caption' => 'Zusätzliche Optionen für Cronjob',
                    'serialize' => 'json'
                ],
                [
                    'is_active',
                    'type' => 'integer',
                    'caption' => 'Aktiv',
                    'values' => [0 => 'Nein', 1 => 'Ja'],
                    'ui' => ['form' => [Dropdown::class]]
                ],
                [
                    'interval',
                    'type' => 'string',
                    'caption' => 'Ausführungshäufigkeit',
                    'values' => $this->intervalSettings,
                    'ui' => ['form' => [Dropdown::class]]
                ],
                [
                    'date_yearly',
                    'type' => 'date',
                    'caption' => 'am diesem Datum (Jahr wird ignoriert)',
                ],
                [
                    'time_yearly',
                    'type' => 'time',
                    'caption' => 'zu dieser Uhrzeit',
                ],
                [
                    'day_monthly',
                    'type' => 'integer',
                    'caption' => 'am diesem Tag (1-28)',
                ],
                [
                    'time_monthly',
                    'type' => 'time',
                    'caption' => 'zu dieser Uhrzeit',
                ],

                [
                    'weekday_weekly',
                    'type' => 'integer',
                    'caption' => 'an Wochentag',
                    'values' => GERMAN_WEEKDAYS,
                    'ui' => ['form' => [Dropdown::class]]
                ],
                [
                    'time_weekly',
                    'type' => 'time',
                    'caption' => 'zu dieser Uhrzeit',
                ],

                [
                    'time_daily',
                    'type' => 'time',
                    'caption' => 'Ausführen um',
                ],
                [
                    'minute_hourly',
                    'type' => 'integer',
                    'caption' => 'Zu dieser Minute ausführen (0-59)',
                ],
                [
                    'interval_minutely',
                    'type' => 'string',
                    'caption' => 'Intervall',
                    'values' => $this->minutelyIntervalSettings,
                    'ui' => ['form' => [Dropdown::class]]
                ],
                [
                    'offset_minutely',
                    'type' => 'integer',
                    'caption' => 'Verschiebung in Minuten (0-14)',
                    'default' => 0,
                ],
                [
                    'last_executed',
                    'type' => 'datetime',
                    'system' => true
                ],
                [
                    'last_execution_success',
                    'type' => 'boolean',
                    'system' => true
                ],
                [
                    'last_execution_duration',
                    'type' => 'float',
                    'system' => true
                ],
                [
                    'last_execution_output',
                    'type' => 'array',
                    'serialize' => 'serialize',
                    'system' => true
                ],
            ]
        );

        $this->addCalculatedField(
            'schedule_info',
            [
                function (Model $record): string {
                    return $record->getScheduleInfo();
                },
                'type' => 'string',
                'caption' => 'Ausführungsintervall',
            ]
        );

        $this->onHook(
            Model::HOOK_BEFORE_SAVE,
            function (self $model, $isUpdate) {
                if (!$model->isDirty('type')) {
                    return;
                }
                $className = $model->get('type');

                $cronClass = new $className(
                    $this->persistence,
                    is_array($model->get('defaults')) ? $model->get('defaults') : []
                );
                $model->set('name', $cronClass->name);
                $model->set('description', $cronClass->description);
            }
        );
    }

    public function run(\DateTime $dateTime = null): void
    {
        //for testing, a dateTime object can be provided. In Normal operation, dont pass anything to use current time
        if (!$dateTime) {
            $dateTime = new \DateTime();
        }

        $this->currentDate = $dateTime->format('m-d');
        $this->currentWeekday = $dateTime->format('N');
        $this->currentDay = $dateTime->format('m');
        $this->currentTime = $dateTime->format('H:i');
        $this->currentMinute = $dateTime->format('i');

        //execute yearly first, minutely last!
        foreach ($this->intervalSettings as $interval => $type) {
            $records = clone $this; //clone here to keep currentDate etc.
            $records->addCondition('interval', $interval);
            $records->addCondition('is_active', 1);

            foreach ($records as $record) {
                $record->executeCronIfScheduleMatches();
            }
        }
    }

    private function executeCronIfScheduleMatches(): void
    {
        //yearly execution
        if ($this->get('interval') === 'YEARLY') {
            $this->executeYearlyIfMatches();
        } //monthly execution
        elseif ($this->get('interval') === 'MONTHLY') {
            $this->executeMonthlyIfMatches();
        } //weekly execution
        elseif ($this->get('interval') === 'WEEKLY') {
            $this->executeWeeklyIfMatches();
        } //daily execution
        elseif ($this->get('interval') === 'DAILY') {
            if (
                !$this->get('time_daily')
                || $this->currentTime !== $this->get('time_daily')->format('H:i')
            ) {
                return;
            }
            $this->executeCron();
        } //hourly
        elseif ($this->get('interval') === 'HOURLY') {
            if (intval($this->currentMinute) !== $this->get('minute_hourly')) {
                return;
            }
            $this->executeCron();
        } elseif ($this->get('interval') === 'MINUTELY') {
            $this->executeMinutelyIfMatches();
        }
    }

    private function executeYearlyIfMatches()
    {
        if (
            !$this->get('date_yearly') instanceof \DateTimeInterface
            || !$this->get('time_yearly') instanceof \DateTimeInterface
        ) {
            return;
        }

        if (
            $this->currentDate !== $this->get('date_yearly')->format('m-d')
            || $this->currentTime !== $this->get('time_yearly')->format('H:i')
        ) {
            return;
        }

        $this->executeCron();
    }


    private function executeMonthlyIfMatches()
    {
        if (
            $this->get('day_monthly') < 1
            || $this->get('day_monthly') > 28
            || !$this->get('time_monthly') instanceof \DateTimeInterface
        ) {
            return;
        }
        if (
            intval($this->currentDay) !== $this->get('day_monthly')
            || $this->currentTime !== $this->get('time_monthly')->format('H:i')) {
            return;
        }

        $this->executeCron();
    }

    private function executeWeeklyIfMatches()
    {
        if (
            $this->get('weekday_weekly') !== (int)$this->currentWeekday
            || $this->currentTime !== $this->get('time_weekly')->format('H:i')
        ) {
            return;
        }

        $this->executeCron();
    }


    private function executeMinutelyIfMatches()
    {
        if ($this->get('interval_minutely') == 'EVERY_MINUTE') {
            $this->executeCron();
        } elseif (
            $this->get('interval_minutely') == 'EVERY_FIFTH_MINUTE'
            && ($this->currentMinute % 5) === $this->get('offset_minutely')
        ) {
            $this->executeCron();
        } elseif (
            $this->get('interval_minutely') == 'EVERY_FIFTEENTH_MINUTE'
            && ($this->currentMinute % 15) === $this->get('offset_minutely')
        ) {
            $this->executeCron();
        }
    }

    //TODO: Make protected? 
    public function executeCron(): bool
    {
        try {
            if (!$this->loaded()) {
                throw new Exception('$this needs to be loaded in ' . __FUNCTION__);
            }

            $this->set('last_executed', new \DateTime());
            $this->save();

            $className = $this->get('type');

            $cronJob = new $className(
                $this->persistence,
                is_array($this->get('defaults')) ? $this->get('defaults') : []
            );
            $startOfCron = microtime(true);

            $cronJob->execute();
            $this->set('last_execution_duration', microtime(true) - $startOfCron);
            $this->set('last_execution_success', true);
            $this->set('last_execution_output', $cronJob->messages);
            $this->save();

            $this->reportSuccess($cronJob);

            return true;
        } //catch any errors as more than one cron could be executed per minutely run
        catch (\Throwable $e) {
            $this->set('last_execution_success', false);
            $this->set('last_execution_output', [$e->getMessage()]);
            $this->save();

            $this->reportFailure($e);
            return false;
        }
    }

    protected function reportSuccess(BaseCronJob $cronJob)
    {
    }

    protected function reportFailure(\Throwable $e)
    {
    }

    /**
     * Loads all Cronjob Files and returns them as array:
     * Fully\Qualified\ClassName => Name property
     */
    public function getAvailableCrons(): array
    {
        $res = [];
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

                //maybe reflection needed in case contructor is not compatible
                $class = new $className($this->persistence);

                $res[get_class($class)] = $class->getName();
            }
        }

        return $res;
    }

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
            return 'Jährlich am ' . $this->get('date_yearly')->format('d.m.Y')
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