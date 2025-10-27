<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configurazione CORS per permettere richieste da Google Apps Script
    |
    */

    'paths' => ['api/*', 'oauth/token'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'], // In produzione, limitare a domini specifici

    'allowed_origins_patterns' => [
        '/^https:\/\/script\.google\.com$/',
        '/^https:\/\/.*\.googleusercontent\.com$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
