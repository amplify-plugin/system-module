<?php

namespace Amplify\System\Jobs;

use Amplify\System\Mail\SendSettingsEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    public $email;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, ...$email)
    {
        $this->data = $data;
        $this->email = $email;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $emails = $this->filterEmails();

        if (empty($emails)) {
            return;
        }

        foreach ($emails as $email) {
            if (strlen($email) > 1) {
                \Mail::to($email)->send(new SendSettingsEmail($this->data));
            }
        }
    }

    private function filterEmails(): array
    {
        if (empty($this->email)) {
            return [];
        }

        return array_filter($this->email, function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });
    }
}
