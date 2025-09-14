<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\CustomerRole;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class ContactServiceJob extends BaseImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Contact $contact;

    public ?CustomerRole $defaultRole;

    public ?int $contact_id = null;

    public ?string $email = null;

    public ?string $password = null;

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

        App::setLocale($this->locale);

        $this->prepareInitialProperty($aCsv);

        $this->defaultRole = CustomerRole::where('is_default', true)->first();
        $contact = Contact::query()->where('id', $this->contact_id)->orWhere('email', $this->email)->first();
        empty($contact) ? $this->handleCreateOperation($aCsv) : $this->handleUpdateOperation($aCsv, $contact);

        App::setLocale($this->default_locale);
    }

    protected function prepareInitialProperty($aCsv): void
    {
        collect($this->column_mapping)->map(function ($item, $index) use ($aCsv) {
            if ($item->field_or_attribute_name === 'id') {
                $this->contact_id = $aCsv[$index];
            }

            if ($item->field_or_attribute_name === 'email') {
                $this->email = $aCsv[$index];
            }

            if ($item->field_or_attribute_name === 'password') {
                $this->password = $aCsv[$index];
            }
        });
    }

    protected function handleCreateOperation($aCsv): void
    {
        $this->contact = new Contact;
        $this->contact->password = $this->password ?? config('amplify.basic.contact_import_default_password');

        if (! $this->password) {
            $this->contact->password_reset_required = true;
        }

        $this->saveDataToDatabase($aCsv);
    }

    protected function handleUpdateOperation($aCsv, $entity): void
    {
        $this->contact = $entity;
        unset($this->contact->password);
        $this->saveDataToDatabase($aCsv);
    }

    protected function saveDataToDatabase($aCsv): void
    {
        DB::transaction(function () {
            $this->handleColumnMapping();
            $this->contact->save();

            if ($this->contact->wasRecentlyCreated && $this->defaultRole) {
                DB::table(config('permission.table_names.model_has_roles'))
                    ->insert([
                        'role_id' => $this->defaultRole->id,
                        'model_type' => Contact::class,
                        'model_id' => $this->contact->id,
                        'team_id' => $this->contact->customer_id,
                    ]);
            }
        });
    }

    protected function mapToField($item, $value): void
    {
        $this->contact->{$item->field_or_attribute_name} = $value;
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
