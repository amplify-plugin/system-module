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
            'ro' => 'reorder'
        ];

        return new Collection($aliases ?? $defaultAliases);
    }

    public static function backendDefaultPermissions(?array $permissions = null): Collection
    {
        $defaultPermissions = config('amplify.backend.permissions', []);

        return new Collection($permissions ?? $defaultPermissions);
    }

    public static function frontendDefaultPermissions(?array $permissions = null): Collection
    {
        $defaultPermissions = config('amplify.frontend.permissions', []);

        return new Collection($permissions ?? $defaultPermissions);
    }
}
