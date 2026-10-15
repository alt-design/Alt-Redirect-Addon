<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Headers
    |--------------------------------------------------------------------------
    |
    | A key/value pair array of Headers to be included with the redirect. This
    | applies to all redirects. For example:
    |
    | 'headers' => [
    |     'Cache-Control' => 'no-cache, must-revalidate',
    | ];
    |
    */

    'headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | Here you may specify the driver to use for storing redirects.
    |
    | Supported: "file", "database"
    |
    */

    'driver' => 'file',
    // 'driver' => 'database',

    /*
    |--------------------------------------------------------------------------
    | Root Path
    |--------------------------------------------------------------------------
    |
    | The path where redirects and query strings are stored as YAML files
    | when using the "file" driver.
    |
    | Default: 'content/alt-redirect'
    |
    */

    'root_path' => 'content/alt-redirect',
];
