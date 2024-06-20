<?php

return [
    'host' => env('COUCHBASE_HOST', 'couchbase://127.0.0.1'),
    'username' => env('COUCHBASE_USER', 'kaustav'),
    'password' => env('COUCHBASE_PASSWORD', 'password'),
    'bucket' => env('COUCHBASE_BUCKET', 'travel-sample'),
];
