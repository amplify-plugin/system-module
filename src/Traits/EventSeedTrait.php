<?php

namespace Amplify\System\Traits;

use Amplify\System\Backend\Models\Event;
use Amplify\System\Backend\Models\EventRecipent;
use Amplify\System\Backend\Models\EventTemplate;
use Amplify\System\Backend\Models\EventVariable;
use Illuminate\Support\Facades\Schema;

trait EventSeedTrait
{
    public function withTruncate()
    {
        return false;
    }


    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        $tables = ['events', 'event_templates', 'event_variables', 'event_recipents'];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                throw new \Exception("Table {$table} required and does not exist");
            }
        }

        if ($this->withTruncate()) {
            Event::truncate();
            EventVariable::truncate();
            EventRecipent::truncate();
            EventTemplate::truncate();
        }


        foreach ($this->data() as $event) {
            $eventVariables = $event['eventVariables'] ?? [];
            unset($event['eventVariables']);
            $eventRecipents = $event['eventRecipents'] ?? [];
            unset($event['eventRecipents']);
            $eventTemplates = $event['eventTemplates'] ?? [];
            unset($event['eventTemplates']);

            $eventModel = Event::create($event);
            $eventModel->eventVariables()->saveMany($eventVariables);
            $eventModel->eventRecipents()->saveMany($eventRecipents);
            $eventModel->eventTemplate()->saveMany($eventTemplates);
        }
    }
}