<?php

namespace Amplify\System\Abstracts;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Illuminate\Support\Str;

abstract class BackpackCustomCrudController extends CrudController
{
    public function __construct()
    {
        if ($this->crud) {
            return;
        }

        $this->middleware(function ($request, $next) {
            $this->crud = app('crud');
            $this->crud->setRequest($request);
            $this->setupDefaults();
            $this->setup();
            $this->setupConfigurationForCurrentOperation();

            $this->crud->removeSaveAction('save_and_edit');
            $this->crud->removeSaveAction('save_and_new');
            $this->crud->removeSaveAction('save_and_preview');

            $this->crud->removeButton('show');
            $this->crud->addButton('top', 'create', 'view', 'backend::buttons.create', 'beginning');

            $operations = ['list', 'create', 'show', 'update', 'delete', 'reorder', 'clone'];

            foreach ($operations as $op) {
                if (!backpack_user()->can("{$this->crud->entity_name}.{$op}")) {
                    $this->crud->denyAccess([$op]);
                }
            }

            return $next($request);
        });
    }
}
