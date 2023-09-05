<?php declare(strict_types=1);

namespace PhilippR\Atk4\Cron;

use Atk4\Core\Exception;
use Atk4\Data\Model;

class ExecutionLog extends Model
{

    public $table = 'execution_log';

    //no reload needed as there are no expressions in this model. Saves Performance
    public bool $reloadAfterSave = false;

    /**
     * @throws Exception
     * @throws \Atk4\Data\Exception
     */
    protected function init(): void
    {
        parent::init();

        $this->addField(
            'execution_datetime',
            [
                'type' => 'datetime',
                'system' => true
            ]
        );

        $this->addField(
            'execution_successful',
            [
                'type' => 'boolean',
                'system' => true
            ]
        );

        $this->addField(
            'execution_duration',
            [
                'type' => 'float',
                'system' => true
            ]
        );

        $this->addField(
            'execution_output',
            [
                'type' => 'json',
                'system' => true
            ]
        );

        $this->hasOne('scheduler_id', ['model' => [Scheduler::class]]);
        $this->setOrder(['execution_datetime' => 'desc']);
    }
}