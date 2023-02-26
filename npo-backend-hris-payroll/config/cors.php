<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS
    |--------------------------------------------------------------------------
    |
    | allowedOrigins, allowedHeaders and allowedMethods can be set to array('*')
    | to accept any value.
    |
    */
    'paths' => ['*'],
    'supports_credentials' => true,
    'allowed_origins' => ['*'],
    'allowed_originsPatterns' => [],
    'allowed_headers' => ['*'],
    'allowed_methods' => ['*'],
    'exposed_headers' => ['Content-Disposition'],
    'max_age' => 0,

];
