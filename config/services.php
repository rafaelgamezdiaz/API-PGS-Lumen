<?php

return [
    'clients' => [
        'base_url'  => env('CUSTOMER_SERVICE_BASE_URL'),
        'port'      => env('CUSTOMER_SERVICE_PORT'),
        'secret'    => env('CUSTOMER_SERVICE_SECRET'),
        'prefix'    => env('CUSTOMER_SERVICE_PREFIX')
    ],
    'users' => [
        'base_url'  => env('USERS_SERVICE_BASE_URL'),
        'port'      => env('USERS_SERVICE_PORT'),
        'secret'    => env('USERS_SERVICE_SECRET'),
        'prefix'    => env('USERS_PREFIX')
    ],
    'sales' => [
        'base_url'  => env('SALES_SERVICE_BASE_URL'),
        'port'      => env('SALES_SERVICE_PORT'),
        'secret'    => env('SALES_SERVICE_SECRET'),
        'prefix'    => env('SALES_SERVICE_PREFIX')
    ]
];
