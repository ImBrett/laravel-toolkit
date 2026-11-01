<?php declare(strict_types=1);

return [
    'infinity' => env('TEMPORAL_INFINITY', '9999-12-31 23:59:59'),
    'user_resolver' => fn () => optional(auth()->user())->email ?? 'system', // @phpstan-ignore method.notFound
];
