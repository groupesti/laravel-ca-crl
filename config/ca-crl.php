<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | CRL Lifetime (hours)
    |--------------------------------------------------------------------------
    |
    | How many hours a generated CRL is valid for. After this period the CRL
    | is considered expired and relying parties should fetch a new one.
    |
    */
    'lifetime_hours' => env('CA_CRL_LIFETIME_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Overlap Hours
    |--------------------------------------------------------------------------
    |
    | Number of hours before a CRL expires that a new one should be generated.
    | This ensures there is always a valid CRL available during the transition.
    |
    */
    'overlap_hours' => env('CA_CRL_OVERLAP_HOURS', 6),

    /*
    |--------------------------------------------------------------------------
    | Auto Generate
    |--------------------------------------------------------------------------
    |
    | When enabled, the scheduler will automatically generate new CRLs for
    | active CAs whose current CRL is about to expire.
    |
    */
    'auto_generate' => env('CA_CRL_AUTO_GENERATE', true),

    /*
    |--------------------------------------------------------------------------
    | Schedule Frequency
    |--------------------------------------------------------------------------
    |
    | How often the auto-generation task should run. Supported values are
    | any method name accepted by Laravel's scheduler (e.g. 'hourly',
    | 'daily', 'everyFourHours').
    |
    */
    'schedule_frequency' => env('CA_CRL_SCHEDULE_FREQUENCY', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Delta CRL
    |--------------------------------------------------------------------------
    |
    | Whether delta CRL generation is enabled. Delta CRLs contain only the
    | changes since the last full CRL, reducing bandwidth for large CAs.
    |
    */
    'delta_crl_enabled' => env('CA_CRL_DELTA_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | The Laravel filesystem disk to use for storing published CRL files.
    |
    */
    'storage_disk' => env('CA_CRL_STORAGE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Storage Path
    |--------------------------------------------------------------------------
    |
    | The base path within the storage disk where CRL files are stored.
    |
    */
    'storage_path' => env('CA_CRL_STORAGE_PATH', 'ca/crls'),

    /*
    |--------------------------------------------------------------------------
    | Distribution URL
    |--------------------------------------------------------------------------
    |
    | The base URL for CRL distribution points. If null, URLs will be
    | generated from the application's route configuration.
    |
    */
    'distribution_url' => env('CA_CRL_DISTRIBUTION_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled' => true,
        'prefix' => 'api/ca/crls',
        'middleware' => ['api'],
    ],

];
