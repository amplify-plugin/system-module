<?php

namespace Amplify\System\Commands;

use App\Models\Contact;
use App\Models\CustomerAddress;
use App\Models\CustomerOrderLine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveUnusedAddressesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:unused-addresses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove unused addresses.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $addresses_ids = CustomerOrderLine::whereNotNull('ship_to_address_id')->pluck('ship_to_address_id')->toArray();
            $addresses_ids += Contact::whereNotNull('customer_address_id')->pluck('customer_address_id')->toArray();

            CustomerAddress::whereNotIn('id', $addresses_ids)->delete();

            $this->info('Successfully removed unused addresses.');
        } catch (\Throwable $th) {
            $this->error($th->getMessage());
        }
    }
}
