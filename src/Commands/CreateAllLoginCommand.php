<?php

namespace Amplify\System\Commands;

use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\ContactLogin;
use Illuminate\Console\Command;

class CreateAllLoginCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create-all-contact-login-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Contact::with('customer', 'customer_address')->each(function (Contact $contact) {
            ContactLogin::firstOrCreate([
                'contact_id' => $contact->getKey(),
                'customer_id' => $contact->customer_id,
                'warehouse_id' => $contact->customer->warehouse_id ?? null,
                'customer_address_id' => $contact->customer_address_id,
                'ship_to_name' => $contact->customer_address?->address_name ?? null,
            ]);
        });
    }
}
