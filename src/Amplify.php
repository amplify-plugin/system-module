<?php

namespace Amplify\System;

use Illuminate\Support\Collection;

class Amplify
{
    /**
     * @param array|null $aliases
     * <table><tbody>
     *     <tr><td>s</td><td>show</td></tr>
     *     <tr><td>c</td><td>create</td></tr>
     *     <tr><td>d</td><td>delete</td></tr>
     *     <tr><td>l</td><td>list</td></tr>
     *     <tr><td>v</td><td>view</td></tr>
     *     <tr><td>a</td><td>add</td></tr>
     *     <tr><td>u</td><td>update</td></tr>
     *     <tr><td>r</td><td>remove</td></tr>
     *     <tr><td>ro</td><td>reorder</td></tr>
     *     <tr><td>dt</td><td>details</td></tr>
     *     </tbody></table>
     * @return Collection
     */
    public static function defaultPermissionAliases(?array $aliases = null): Collection
    {
        $defaultAliases = [
            's' => 'show',
            'c' => 'create',
            'd' => 'delete',
            'l' => 'list',
            'v' => 'view',
            'a' => 'add',
            'u' => 'update',
            'r' => 'remove',
            'ro' => 'reorder',
            'dt' => 'details',
        ];

        return new Collection($aliases ?? $defaultAliases);
    }

    public static function backendDefaultPermissions(?array $permissions = null): Collection
    {
        $defaultPermissions = [];

        if (function_exists('backend_permissions')) {
            $defaultPermissions = backend_permissions();
        }

        return new Collection($permissions ?? $defaultPermissions);
    }

    public static function frontendDefaultPermissions(?array $permissions = null): Collection
    {
        $defaultPermissions = [];

        if (function_exists('frontend_permissions')) {
            $defaultPermissions = frontend_permissions();
        }

        return new Collection($permissions ?? $defaultPermissions);
    }
}
