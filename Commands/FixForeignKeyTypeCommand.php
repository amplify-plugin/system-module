<?php

namespace Amplify\System\Commands;

use Amplify\ErpApi\Facades\ErpApi;
use App\Models\CartItem;
use App\Models\CustomerOrderLine;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class FixForeignKeyTypeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-foreign-key-issue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $progressBar = $this->output->createProgressBar(8);
        $progressBar->start();

        Schema::disableForeignKeyConstraints();
        $this->info('Disabled database foreign key check.');
        $progressBar->advance();

        $customerOrderTableColumns = collect(getColumnListing('customer_orders'));
        Schema::table('customer_orders', function (Blueprint $table) use ($customerOrderTableColumns) {
            if ($this->tableColumnNeedChange($customerOrderTableColumns, 'customer_id', 'varchar')) {
                $table->foreignId('customer_id')->nullable()->change();
                $table->foreign('customer_id')->references('id')->on('customers');
            }

            if ($this->tableColumnNeedChange($customerOrderTableColumns, 'contact_id', 'varchar')) {
                $table->foreignId('contact_id')->nullable()->change();
                $table->foreign('contact_id')->references('id')->on('contacts');
            }

            if ($this->tableColumnNeedChange($customerOrderTableColumns, 'user_id', 'varchar')) {
                $table->foreignId('user_id')->nullable()->change();
                $table->foreign('user_id')->references('id')->on('users');
            }
        });
        $this->info('Customer order table foreign key modification done.');
        $progressBar->advance();

        if (! Schema::hasColumn('customer_order_lines', 'warehouse_id_backup')) {
            Schema::table('customer_order_lines', function (Blueprint $table) {
                $table->string('warehouse_id_backup')->nullable()->after('warehouse_id');
            });
        }
        $this->info('Customer order line added warehouse id backup column.');
        $progressBar->advance();

        $customerOrderLineTableColumns = collect(getColumnListing('customer_order_lines'));
        if ($this->tableColumnNeedChange($customerOrderLineTableColumns, 'warehouse_id', 'varchar')) {
            $warehouses = ErpApi::getWarehouses();
            CustomerOrderLine::all()->each(function (CustomerOrderLine $orderLine) use ($warehouses) {
                $orderLine->warehouse_id_backup = $orderLine->warehouse_id;

                if ($orderLine->warehouse_id == null || $orderLine->warehouse_id == '') {
                    $orderLine->warehouse_id = null;
                } else {
                    if ($warehouse = $warehouses->firstWhere('WarehouseNumber', $orderLine->warehouse_id)) {
                        $orderLine->warehouse_id = $warehouse->InternalId;
                    }
                }

                if (! is_numeric($orderLine->warehouse_id)) {
                    $orderLine->warehouse_id = null;
                }

                if ($orderLine->isDirty()) {
                    $orderLine->save();
                }
            });
        }
        $this->info('Customer order line table warehouse key modification done.');
        $progressBar->advance();

        Schema::table('customer_order_lines', function (Blueprint $table) use ($customerOrderLineTableColumns) {
            if ($this->tableColumnNeedChange($customerOrderLineTableColumns, 'customer_order_id', 'varchar')) {
                $table->foreignId('customer_order_id')->nullable()->change();
                $table->foreign('customer_order_id')->references('id')->on('customer_orders');
            }

            if ($this->tableColumnNeedChange($customerOrderLineTableColumns, 'warehouse_id', 'varchar')) {
                $table->foreignId('warehouse_id')->nullable()->change();
                $table->foreign('warehouse_id')->references('id')->on('warehouses');
            }

            // if ($this->tableColumnNeedChange($customerOrderLineTableColumns, 'ship_to_address_id', 'varchar')) {
            //     $table->foreignId('ship_to_address_id')->nullable()->change();
            //     $table->foreign('ship_to_address_id')->references('id')->on('customer_addresses');
            // }
        });
        $this->info('Customer order line table warehouse, customer id, ship address id key modification done.');
        $progressBar->advance();

        Artisan::call('migrate', ['--path' => 'database/migrations/2023_12_26_135406_update_add_warehouse_id_in_cart_items_table.php']);
        $this->info('Cart item warehouse id table migration missing added.');
        $progressBar->advance();

        if (Schema::hasColumn('cart_items', 'warehouse_id')) {
            $warehouses = ErpApi::getWarehouses();
            CartItem::all()->each(function (CartItem $cartItem) use ($warehouses) {
                if ($warehouse = $warehouses->firstWhere('WarehouseNumber', $cartItem->product_warehouse_code)) {
                    $cartItem->warehouse_id = $warehouse->InternalId;
                }

                if ($cartItem->isDirty()) {
                    $cartItem->save();
                }
            });
        }
        $this->info('Updating warehoue if of cart item');
        $progressBar->advance();

        Schema::enableForeignKeyConstraints();
        $this->info('Enabled foreign key check');
        $progressBar->finish();

        return self::SUCCESS;
    }

    private function tableColumnNeedChange(Collection &$collection, string $column, string $type): bool
    {
        return $collection->contains(function ($value, $key) use ($column, $type) {
            return $value['name'] == $column && str_contains($value['type'], $type);
        });
    }
}
