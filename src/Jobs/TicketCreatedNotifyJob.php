<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Traits\NotificationEventTrait;
use Amplify\System\Ticket\Models\Ticket;
use Amplify\System\Ticket\Models\TicketDepartment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TicketCreatedNotifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NotificationEventTrait;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $eventCode,
        public Ticket $ticket
    ) {
        $this->eventCode = $eventCode;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->getNecessaryItems();

        foreach ($this->eventInfo->eventActions as $eventAction) {
            if ($eventAction->eventTemplate->notification_type == 'emailable') {
                $this->emailService->sendTicketCreatedNotificationEmail($eventAction, $this->ticket);
            }
        }
    }
}
