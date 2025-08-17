<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Service Name
    |--------------------------------------------------------------------------
    |
    | This value is the default service name that will be used when generating
    | trace codes if no service name is explicitly provided.
    |
    */

    'default_service' => env('TRACE_CODE_DEFAULT_SERVICE', 'APP'),

    /*
    |--------------------------------------------------------------------------
    | Trace Code Format
    |--------------------------------------------------------------------------
    |
    | Configure the format of generated trace codes. You can customize
    | the length of different components.
    |
    */

    'format' => [
        'service_code_length' => 3,
        'method_hash_length' => 5,
        'random_suffix_length' => 4,
        'timestamp_format' => 'ymdHis', // Y-m-d H:i:s format
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the database table and connection for trace codes.
    |
    */

    'database' => [
        'connection' => env('TRACE_CODE_DB_CONNECTION', null),
        'table' => env('TRACE_CODE_TABLE', 'trace_codes'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Enable caching for trace codes to improve performance.
    |
    */

    'cache' => [
        'enabled' => env('TRACE_CODE_CACHE_ENABLED', false),
        'ttl' => env('TRACE_CODE_CACHE_TTL', 3600), // 1 hour
        'prefix' => env('TRACE_CODE_CACHE_PREFIX', 'trace_code_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Configure validation rules for trace code parameters.
    |
    */

    'validation' => [
        'service_max_length' => 50,
        'method_max_length' => 100,
        'class_max_length' => 255,
        'description_max_length' => 500,
    ],

];