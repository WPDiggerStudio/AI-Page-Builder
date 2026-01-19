<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection
    |--------------------------------------------------------------------------
    |
    | The default queue connection to use.
    |
    | Supported: "sync", "cron", "database"
    |
    */
    'driver' => env('QUEUE_CONNECTION', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Configuration for each queue driver.
    |
    */
    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'cron' => [
            'driver' => 'cron',
            'max_jobs_per_run' => 10,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'wpjarvis_jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | Configuration for storing failed jobs.
    |
    */
    'failed' => [
        'driver' => 'database',
        'table' => 'wpjarvis_failed_jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | Configuration for job batching support.
    |
    */
    'batching' => [
        'table' => 'wpjarvis_job_batches',
    ],

];
