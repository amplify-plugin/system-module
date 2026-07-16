<?php

namespace Amplify\System\Abstracts;

use Amplify\System\Backend\Models\Permission;
use Amplify\System\Backend\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;

abstract class BackpackCustomCrudController extends CrudController
{

    public function setupCrudController($operation = null)
    {
        parent::setupCrudController($operation);

        //Amplify Customization
        $this->crud->removeSaveAction('save_and_edit');
        $this->crud->removeSaveAction('save_and_new');
        $this->crud->removeSaveAction('save_and_preview');

        if ($this->crud->buttons()->firstWhere('name', 'create')) {
            $this->crud->modifyButton('create', ['content' => 'backend::buttons.create']);
        }

        $this->applyPermissions();
    }

    private function applyPermissions(): void
    {
        Permission::selectRaw("`name`, REPLACE(`name`, '{$this->crud->entity_name}.', '') as `operation`")
            ->where('guard_name', User::AUTH_GUARD)
            ->where('name', 'like', "{$this->crud->entity_name}.%")
            ->get()
            ->each(function ($item) {
                backpack_user()->can($item->name)
                    ? $this->crud->allowAccess([$item->operation])
                    : $this->crud->denyAccess([$item->operation]);
            });
    }


    public function __trash()
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


            return $next($request);
        });
    }
}
