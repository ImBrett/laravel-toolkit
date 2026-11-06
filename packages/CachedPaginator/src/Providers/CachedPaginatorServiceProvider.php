<?php declare(strict_types=1);

namespace ImBrett\LaravelCachedPaginator\Providers;

use Illuminate\Support\ServiceProvider;
use ImBrett\LaravelCachedPaginator\Support\CacheKeyResolver;

class CachedPaginatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/cached-paginator.php', 'cached-paginator');
        $this->app->singleton(CacheKeyResolver::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/cached-paginator.php' => config_path('cached-paginator.php'),
        ], 'cached-paginator-config');
    }
}
