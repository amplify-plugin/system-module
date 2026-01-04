<?php

namespace Amplify\System\Marketing\Http\Controllers;

use Amplify\System\Marketing\Http\Request\MerchandisingZoneRequest;
use Amplify\System\Abstracts\BackpackCustomCrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\Pro\Http\Controllers\Operations\FetchOperation;
use Illuminate\Http\JsonResponse;

/**
 * Class MerchandisingZoneCrudController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class MerchandisingZoneCrudController extends BackpackCustomCrudController
{
    use CreateOperation;
    use DeleteOperation;
    use FetchOperation;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\Amplify\System\Marketing\Models\MerchandisingZone::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/merchandising-zone');
        CRUD::setEntityNameStrings('merchandising-zone', 'merchandising zones');
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
        CRUD::column('id')->type('number')->thousands_sep('');
        CRUD::column('name');
        CRUD::column('description');
        CRUD::column('easyask_key');
        CRUD::column('updated_at');

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']);
         */
    }

    public function setupShowOperation()
    {
        CRUD::column('id')->type('number')->thousands_sep('');
        CRUD::column('name');
        CRUD::column('description');
        CRUD::column('easyask_key');
        CRUD::column('updated_at');
        CRUD::button('delete')->remove();
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(MerchandisingZoneRequest::class);

        $this->data['merchandising_zone'] = $this->crud->model->find(request()->id);

        CRUD::field('name');
        CRUD::field('description');
        CRUD::field('easyask_key');

        $this->crud->setCreateView('backend::pages.merchandising_zone.create');
        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number']));
         */
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
        $this->crud->setUpdateView('backend::pages.merchandising_zone.create');

        $this->setupCreateOperation();
    }

    public function fetchMerchandisingZoneSlug(): JsonResponse
    {
        $easyask_key = request()->easyask_key;
        $id = request()->id ?? null;

        return response()->json([
            'easyask_key' => getMerchandisingZoneSlug($easyask_key, $id),
        ]);
    }
}
