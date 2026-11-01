<?php declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Cache duration (seconds)
    |--------------------------------------------------------------------------
    |
    | Determines how long the total count for a paginated query should be
    | cached. Set to 0 to disable caching globally.
    |
    */
    'ttl' => env('CACHED_PAGINATOR_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Cache key prefix
    |--------------------------------------------------------------------------
    |
    | Prefixed to every cache entry created by the cached paginator builder.
    |
    */
    'prefix' => env('CACHED_PAGINATOR_PREFIX', 'pagination'),

    /*
    |--------------------------------------------------------------------------
    | Result threshold
    |--------------------------------------------------------------------------
    |
    | If the computed total is below this threshold the value will not be cached
    | to avoid polluting the store with tiny result sets. Set to 0 to always
    | cache.
    |
    */
    'threshold' => (int)env('CACHED_PAGINATOR_THRESHOLD', 0),
];
