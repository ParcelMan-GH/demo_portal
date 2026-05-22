<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Back Office URL Prefix
    |--------------------------------------------------------------------------
    |
    | Keep route names stable as admin.* while allowing the public URL prefix to
    | change per environment. Local/dev can use /admin; production can use a
    | less obvious prefix without changing controllers or views.
    |
    */
    'prefix' => trim((string) env('BACKOFFICE_PREFIX', 'admin'), '/'),

    'modules' => [
        'dashboard' => 'Dashboard',
        'warehouse' => 'Warehouse Operations',
        'warehouses' => 'Warehouse Management',
        'vendors' => 'Vendors',
        'shipments' => 'Orders',
        'drivers' => 'Drivers',
        'reports' => 'Reports',
        'recipient_payments' => 'Recipient Payments',
        'users' => 'Users',
        'roles' => 'Roles',
        'settings' => 'Settings',
        'marketing' => 'Marketing',
    ],
];
