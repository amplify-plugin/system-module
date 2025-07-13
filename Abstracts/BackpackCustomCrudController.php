<?php

namespace Amplify\System\Abstracts;

use Backpack\CRUD\app\Http\Controllers\CrudController;

abstract class BackpackCustomCrudController extends CrudController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function ($request, $next) {
            if (! backpack_user()->can($this->crud->entity_name.'.list')) {
                $this->crud->denyAccess('list');
            }

            //            if (! backpack_user()->can($this->crud->entity_name.'.show')) {
            //            $this->crud->denyAccess('show');
            //            }

            if (! backpack_user()->can($this->crud->entity_name.'.reorder')) {
                $this->crud->denyAccess('reorder');
            }

            if (! backpack_user()->can($this->crud->entity_name.'.create')) {
                $this->crud->denyAccess('create');
            }

            if (! backpack_user()->can($this->crud->entity_name.'.update')) {
                $this->crud->denyAccess('update');
            }

            if (! backpack_user()->can($this->crud->entity_name.'.delete')) {
                $this->crud->denyAccess('delete');
            }

            $this->crud->removeSaveAction('save_and_edit');
            $this->crud->removeSaveAction('save_and_new');
            $this->crud->removeSaveAction('save_and_preview');
            //            $this->crud->removeButton('show');

            return $next($request);
        });

    }
}
