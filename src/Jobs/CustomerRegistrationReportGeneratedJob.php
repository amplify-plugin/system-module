<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Customer;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Backend\Traits\NotificationEventTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CustomerRegistrationReportGeneratedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NotificationEventTrait, Queueable, SerializesModels;

    private $data;

    /**
     * Create a new job instance.
     */
    public function __construct($event_code, $args)
    {
        $this->eventCode = $event_code;
        $this->data = $args;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->getNecessaryItems();
        $data = [
            '__interval_in_days__' => $this->data['interval'],
            '__report_start_date__' => now()->subDays($this->data['interval'])->format(config('amplify.basic.date_time_format')),
            '__report_end_date__' => now()->format(config('amplify.basic.date_time_format')),
            'attachments' => [$this->data['filepath']]
        ];

        foreach ($this->eventInfo->eventActions as $eventAction) {
            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->sendCustomerRegistrationReportNotification($eventAction, $data);
            }
        }
    }
}
