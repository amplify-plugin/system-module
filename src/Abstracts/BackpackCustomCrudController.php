<?php

namespace Amplify\System\Abstracts;

use Amplify\System\Backend\Models\Permission;
use Amplify\System\Backend\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;

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

            Permission::selectRaw("`name`, REPLACE(`name`, '{$this->crud->entity_name}.', '') as `operation`")
                ->where('guard_name', User::AUTH_GUARD)
                ->where('name', 'like', "{$this->crud->entity_name}.%")
                ->get()
                ->each(function ($item) {
                    (backpack_user()->can($item->name))
                        ? $this->crud->allowAccess([$item->operation])
                        : $this->crud->denyAccess([$item->operation]);
                });

            return $next($request);
        });
    }
}
