<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Scheduling Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the task scheduler settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    |
    | The timezone to use for scheduled tasks. Leave null to use WordPress
    | timezone settings.
    |
    */
    'timezone' => null,

    /*
    |--------------------------------------------------------------------------
    | Prevent Overlapping
    |--------------------------------------------------------------------------
    |
    | Default setting for preventing task overlap.
    |
    */
    'prevent_overlapping' => true,

    /*
    |--------------------------------------------------------------------------
    | Mutex Expiration
    |--------------------------------------------------------------------------
    |
    | Default mutex expiration time in seconds.
    |
    */
    'mutex_expiration' => 1440,

    /*
    |--------------------------------------------------------------------------
    | Log Tasks
    |--------------------------------------------------------------------------
    |
    | Whether to log task execution.
    |
    */
    'log_tasks' => true,

    /*
    |--------------------------------------------------------------------------
    | Custom Schedules
    |--------------------------------------------------------------------------
    |
    | Define custom cron schedules for WordPress.
    |
    */
    'schedules' => [
        'every_minute' => [
            'interval' => 60,
            'display' => 'Every Minute',
        ],
        'every_five_minutes' => [
            'interval' => 300,
            'display' => 'Every 5 Minutes',
        ],
        'every_ten_minutes' => [
            'interval' => 600,
            'display' => 'Every 10 Minutes',
        ],
        'every_fifteen_minutes' => [
            'interval' => 900,
            'display' => 'Every 15 Minutes',
        ],
        'every_thirty_minutes' => [
            'interval' => 1800,
            'display' => 'Every 30 Minutes',
        ],
    ],

];
