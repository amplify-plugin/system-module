<?php

namespace Amplify\System\Marketing\Http\Controllers;

use Amplify\System\Marketing\Http\Request\SubscriberRequest;
use Amplify\System\Abstracts\BackpackCustomCrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class SubscriberCrudController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SubscriberCrudController extends BackpackCustomCrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\Amplify\System\Marketing\Models\Subscriber::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/subscriber');
        CRUD::setEntityNameStrings('subscriber', 'subscribers');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     *
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addFilter(
            [
                'name' => 'status',
                'type' => 'dropdown',
                'label' => 'Status',
            ],
            function () {
                return [
                    'subscribed' => 'Subscribed',
                    'unsubscribed' => 'Unsubscribed',
                ];
            },
            function ($value) {
                $this->crud->addClause('where', 'status', '=', $value);
            }
        );

        CRUD::addColumn([
            'name' => 'id',
        ]);

        CRUD::addColumn([
            'name' => 'email',
        ]);

        CRUD::addColumn([
            'name' => 'attempts',
        ]);
        CRUD::addColumn([
            'name' => 'status',
        ]);

        // $this->crud->setListView('backend::pages.under-construction');

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']);
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     *
     * @return void
     */
    protected function setupCreateOperation($hide_attempt = null)
    {
        CRUD::setValidation(SubscriberRequest::class);

        CRUD::addField([
            'name' => 'email',
            'label' => 'Email',
            'type' => 'email',
        ]);

        CRUD::addField([
            'name' => 'attempts',
            'label' => 'Attempts',
            'type' => 'hidden',
            'default' => 1,

        ]);

        CRUD::addField([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'radio',
            'options' => ['subscribed' => 'Subscribed',
                'unsubscribed' => 'Unsubscribed',
            ],
            'inline' => true,
            'default' => 'subscribed',

        ]);

        // CRUD::setFromDb(); // fields

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number']));
         */
    }

    public function setupShowOperation()
    {
        CRUD::column('email');
        CRUD::column('attempts');
        CRUD::column('status');
        CRUD::column('created_at');
        CRUD::column('updated_at');
        CRUD::button('delete')->remove();
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation(true);
    }
}
