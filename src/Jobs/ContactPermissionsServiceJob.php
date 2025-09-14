<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\Customer;
use Amplify\System\Backend\Models\Permission;
use Amplify\System\Helpers\UtilityHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ContactPermissionsServiceJob extends BaseImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?Contact $contact;

    public ?Customer $customer = null;

    public array $permissions = [];

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->className = get_class($this);

        parent::__construct($data);
    }

    protected function getMappingProcessed($aCsv): void
    {
        echo PHP_EOL, "## $this->className :: getMappingProcessed() ##", PHP_EOL, PHP_EOL;

        $this->prepareInitialProperty($aCsv);
        $this->saveDataToDatabase($aCsv);
    }

    protected function prepareInitialProperty($aCsv): void
    {
        $customer_code = $contact_email = null;
        collect($this->column_mapping)->map(function ($item, $index) use ($aCsv, &$contact_email, &$customer_code) {
            if (! empty($item->field_or_attribute_name) && $item->map_to !== 'Ignore') {
                if ($item->field_or_attribute_name[0] === 'email') {
                    $contact_email = $aCsv[$index];
                } elseif ($item->field_or_attribute_name[0] === 'customer_code') {
                    $customer_code = $aCsv[$index];
                } elseif ($item->map_to === 'Field') {
                    $this->mapToField($item, $aCsv[$index]);
                }
            }
        });

        $this->contact = Contact::where('email', $contact_email)->first();
        $this->customer = ! empty($customer_code) ? Customer::where('customer_code', $customer_code)->first() : ($this->contact?->customer ?? null);
    }

    protected function handleCreateOperation($aCsv): void
    {
        //
    }

    protected function handleUpdateOperation($aCsv, $entity): void
    {
        //
    }

    protected function saveDataToDatabase($aCsv): void
    {
        if ($this->contact && $this->customer) {
            $oldPermissions = DB::table(config('permission.table_names.model_has_permissions'))
                ->where([
                    'model_type' => Contact::class,
                    'model_id' => $this->contact->id,
                    'team_id' => $this->customer->id,
                ])
                ->get()
                ->map(fn ($item) => [
                    'permission_id' => $item->permission_id,
                    'model_type' => $item->model_type,
                    'model_id' => $item->model_id,
                    'team_id' => $item->team_id,
                ]);

            $newPermissions = Permission::select('id')
                ->where('guard_name', 'customer')
                ->whereIn('name', $this->permissions)
                ->get()
                ->map(fn ($item) => [
                    'permission_id' => $item->id,
                    'model_type' => Contact::class,
                    'model_id' => $this->contact->id,
                    'team_id' => $this->customer->id,
                ]);

            if ($newPermissions->count()) {
                $permissions = $oldPermissions->merge($newPermissions);

                DB::table(config('permission.table_names.model_has_permissions'))
                    ->where([
                        'model_type' => Contact::class,
                        'model_id' => $this->contact->id,
                        'team_id' => $this->customer->id,
                    ])->delete();

                DB::table(config('permission.table_names.model_has_permissions'))->insert(
                    $permissions->unique('permission_id')->toArray()
                );
            }
        }
    }

    protected function mapToField($item, $value): void
    {
        if (UtilityHelper::typeCast($value, 'bool')) {
            $this->permissions = [...$this->permissions, ...$item->field_or_attribute_name];
        }
    }

    protected function mapToAttribute($item, $value): void
    {
        //
    }

    protected function mapToTable($item, $value): void
    {
        //
    }
}
