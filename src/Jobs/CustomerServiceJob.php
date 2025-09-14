<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Customer;
use Amplify\System\Backend\Models\CustomerAddress;
use Amplify\System\Backend\Models\Warehouse;
use ErrorException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class CustomerServiceJob extends BaseImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $customerAddress;

    public $customer;

    public $customer_id;

    public $hasAnyCustomerAddress = false;

    public $customerAddressFields = ['address_name', 'address', 'zip_code', 'office_phone_number', 'office_email', ''];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->className = get_class($this);

        parent::__construct($data);
    }

    protected function getMappingProcessed($aCsv): void
    {
        echo PHP_EOL, "## $this->className :: getMappingProcessed() ##", PHP_EOL, PHP_EOL;

        App::setLocale($this->locale);

        $this->prepareInitialProperty($aCsv);

        $customer = Customer::query()->find($this->customer_id);
        empty($customer)
            ? $this->handleCreateOperation($aCsv)
            : $this->handleUpdateOperation($aCsv, $customer);

        App::setLocale($this->default_locale);
    }

    protected function prepareInitialProperty($aCsv): void
    {
        collect($this->column_mapping)
            ->map(function ($item, $index) use ($aCsv) {
                if ($item->field_or_attribute_name === 'id') {
                    $this->customer_id = $aCsv[$index];
                }

                if (! $this->hasAnyCustomerAddress && $item->map_to !== 'Ignore' && in_array($item->field_name, $this->customerAddressFields)) {
                    $this->hasAnyCustomerAddress = true;
                    $customerAddress = CustomerAddress::query()->where('customer_id', $this->customer_id)->first();
                    $this->customerAddress = ! empty($customerAddress) ? $customerAddress : new CustomerAddress;
                }
            });
    }

    protected function handleCreateOperation($aCsv): void
    {
        $this->customer = new Customer;

        if ($this->hasAnyCustomerAddress) {
            $this->customerAddress = new CustomerAddress;
        }

        $this->saveDataToDatabase($aCsv);
    }

    protected function handleUpdateOperation($aCsv, $entity): void
    {
        $this->customer = $entity;
        $this->generateInstanceOfRelatedTable();
        $this->saveDataToDatabase($aCsv);
    }

    protected function generateInstanceOfRelatedTable(): void
    {
        // Generating customerAddress instance
        $customerAddress = CustomerAddress::query()->where([
            'customer_id' => $this->customer_id,
        ])->first();

        $this->customerAddress = ! empty($customerAddress)
            ? $customerAddress
            : new CustomerAddress;
    }

    protected function saveDataToDatabase($aCsv): void
    {
        \DB::transaction(function () {
            $this->handleColumnMapping();
            $this->customer->save();

            /* Saving customerAddress */
            if ($this->hasAnyCustomerAddress) {
                $this->customerAddress->customer_id = $this->customer_id;
                $this->customerAddress->save();
            }
        });
    }

    /**
     * @throws ErrorException
     */
    protected function mapToField($item, $value): void
    {
        if ($item->field_or_attribute_name === 'is_suspended') {
            $this->customer->{$item->field_or_attribute_name} = strtolower($value) == 'y' ? true : false;
        } elseif ($item->field_or_attribute_name === 'warehouse_id') {
            $this->customer->{$item->field_or_attribute_name} = is_int($value) ? $value : optional(Warehouse::firstWhere('code', $value))->id;
        } else {
            $this->customer->{$item->field_or_attribute_name} = $value;
        }
    }

    protected function mapToAttribute($item, $value): void
    {
        //
    }

    protected function mapToTable($item, $value): void
    {
        $modelName = $item->field_or_attribute_name;
        $field_name = $item->field_name;
        $separator = $item->separator;

        switch ($modelName) {
            case 'Customer Address':
                if (! empty($value)) {
                    $this->customerAddress->{$field_name} = $value;
                }

                break;
            default:
                break;
        }
    }
}
