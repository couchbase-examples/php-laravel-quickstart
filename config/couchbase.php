<?php

return [
    'host' => env('DB_CONN_STR', 'couchbase://127.0.0.1'),
    'username' => env('DB_USERNAME', 'Administrator'),
    'password' => env('DB_PASSWORD', 'password'),
    'bucket' => env('DB_BUCKET', 'travel-sample'),
];
