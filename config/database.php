<?php

declare(strict_types=1);

/**
 * Database Configuration
 *
 * Uses WordPress database connection by default.
 *
 * @package App
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    */
    'default' => env('DB_CONNECTION', 'wordpress'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */
    'connections' => [
        'wordpress' => [
            'driver' => 'mysql',
            'host' => defined('DB_HOST') ? DB_HOST : '127.0.0.1',
            'port' => env('DB_PORT', '3306'),
            'database' => defined('DB_NAME') ? DB_NAME : '',
            'username' => defined('DB_USER') ? DB_USER : '',
            'password' => defined('DB_PASSWORD') ? DB_PASSWORD : '',
            'charset' => defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => $GLOBALS['wpdb']->prefix ?? 'wp_',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', storage_path('database.sqlite')),
            'prefix' => '',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    */
    'migrations' => 'bra_calculator_migrations',
];
