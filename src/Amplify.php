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
    public static function defaultPermissionAliases(?array $aliases = null) : Collection
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
        $defaultPermissions = [
            'system-config' => 'u,cache-clear', // okay
            'attribute' => 'l,s,c,u,d', // okay
            'category' => 'l,s,c,u,d,ro', // okay
            'contact' => 'l,s,c,u,d,impersonate', // okay
            'customer' => 'l,s,c,u,d,erp-bulk-sync', // okay
            'product' => 'l,s,c,u,d,remove-sku,publish,unpublish', // okay
            'classification' => 'l,s,c,u,d', // okay
            'account-title' => 'l,s,c,u,d', // okay
            'siteconfig' => 'l,s,c,u,d', // okay
            'option' => 'l,s,c,u,d', // okay
            'manufacturer' => 'l,s,c,u,d', // okay
            'brand' => 'l,s,c,u,d', // okay
            'export' => 'l,s,c,u,d', // okay
            'country' => 'l,s,c,u,d', // okay
            'scheduled-job' => 'l,s,c,u,d', // okay
            'backup' => 'l,c,d,download', // okay
            'query-category' => 'l,s,c,u,d',
            'saved-query' => 'l,s,c,u,d',
            'saved-report' => 'l,s,c,u,d',
            'dynamic-report' => 'l',
            'order' => 'l,s,c,u,d',
            'quote' => 'l,s,c,u,d', // okay
            'invoice' => 'l,s,c,u,d', // okay
            'payment' => 'l,s,c,u,d', // okay
            'template' => 'l,s,c,u,d', // okay
            'page' => 'l,s,c,u,d', // okay
            'widget' => 'l,s,c,u,d', // okay
            'form' => 'l,s,c,u,d', // okay
            'form-response' => 'l,s,c,u,d', // okay
            'ticket' => 'l,s,c,u,d', // okay
            'ticket-department' => 'l,s,c,u,d', // okay
            'faq' => 'l,s,c,u,d', // okay
            'campaign' => 'l,s,c,u,d',
            'subscriber' => 'l,s,c,u,d',
            'shipping' => 'l,s,c,u,d', // okay
            'tax' => 'l,s,c,u,d', // okay
            'warehouse' => 'l,s,c,u,d', // okay
            'store' => 'l,s,c,u,d', // okay
            'site-pricing' => 'l,s,c,u,d',
            'import-definition' => 'l,s,c,u,d', // okay
            'import-job' => 'l,s,c,u,d', // okay
            'data-transformation' => 'l,s,c,u,d', // okay
            'customer-group' => 'l,s,c,u,d', // okay
            'customer-registration' => 'l,s,u,d', // okay
            'language' => 'l,s,c,u,d', // okay
            'localization' => 'l,s,c,u,d', // okay
            'content-category' => 'l,s,c,u,d', // okay
            'content' => 'l,s,c,u,d', // okay
            'merchandising-zone' => 'l,s,c,u,d', // okay
            'footer' => 'l,s,c,u,d', // okay
            'menu' => 'l,s,c,u,d,ro',
            'mega-menu' => 'l,s,c,u,d',
            'menu-group' => 'l,s,c,u,d', // list
            'document-type' => 'l,s,c,u,d', // okay
            'faq-category' => 'l,s,c,u,d', // okay
            'customer-list' => 'l,s,c,u,d',
            'icecat-definition' => 'l,s,c,u,d', // okay
            'icecat-transformation' => 'l,s,c,u,d', // okay
            'banner' => 'l,s,c,u,d', // okay
            'banner-zone' => 'l,s,c,u,d', // okay
            'script-manager' => 'l,s,c,u,d', // okay
            'trigger' => 'l,s,u', // okay
            'event-action' => 'l,s,c,u,d', // okay
            'event-template' => 'l,s,c,u,d', // okay
            'product-sync' => 'l,s,c,u,d,process', // okay
            'customer-permission' => 'l,s,c,u,d', // okay
            'customer-role' => 'l,s,c,u,d', // okay
            'permission' => 'l,s,c,u,d', // okay
            'user' => 'l,s,c,u,d', // okay
            'role' => 'l,s,c,u,d', // okay
            'tag' => 'l,s,c,u,d', // okay
            'sitemap' => 'l,s,c,u,d', // okay
            'audit' => 'l,s', // okay
            'message' => 'l,s,c,u', // okay
            'file-manager' => 'l', // okay
            'order-rule' => 'l,s,c,u,d', // okay
            'customer-order-rule' => 'l,s,c,u,d', // okay
            'customer-order-rule-track' => 'l,s,c,u,d', // okay
            'event-type' => 'l,s,c,u,d', // okay
            'company' => 'l,s,c,u,d', // okay
            'custom-part-number' => 'l,s,c,u,d', // okay
            'relationship-type' => 'l,s,c,u,d', // okay
            'product-relation' => 'l,s,c,u,d', // okay
            'customer-address' => 'l,s,c,u,d', // okay
        ];

        return new Collection($permissions ?? $defaultPermissions);
    }

    public static function frontendDefaultPermissions(?array $permissions = null): Collection
    {
        $defaultPermissions = [
            'account-summary' => 'allow-account-summary',
            'checkout' => 'choose-warehouse,choose-shipto,credit-card-payment,payment-on-accounts',
            'contact-management' => 'l,v,a,u,r',
            'dashboard' => 'allow-dashboard',
            'favorites' => 'manage-global-list,use-global-list,manage-personal-list',
            'invoices' => 'v,pay',
            'login-management' => 'manage-logins,impersonate',
            'message' => 'messaging',
            'order' => 'v,c,add-to-cart',
            'order-approval' => 'approve',
            'order-processing-rules' => 'manage-rules',
            'order-rejected' => 'l,v',
            'past-items' => 'past-items-list, past-items-history',
            'profile' => 'change-start-page',
            'quote' => 'v,rfq',
            'reports' => 'summary',
            'role' => 'v,manage',
            'saved-carts' => 'l',
            'ship-to-addresses' => 'l,v,a,u,r',
            'shop' => 'add-to-cart,browse',
            'switch-account' => 'switch-account',
            'ticket' => 'tickets',
        ];

        return new Collection($permissions ?? $defaultPermissions);
    }
}
