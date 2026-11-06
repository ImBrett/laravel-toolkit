<?php declare(strict_types=1);

namespace ImBrett\LaravelSluggable\Providers;

use Illuminate\Support\ServiceProvider;
use ImBrett\LaravelSluggable\Contracts\SlugGenerator;
use ImBrett\LaravelSluggable\Contracts\SlugUniqueResolver;
use ImBrett\LaravelSluggable\Support\Generators\DefaultSlugGenerator;
use ImBrett\LaravelSluggable\Support\SlugService;
use ImBrett\LaravelSluggable\Support\Uniqueness\DatabaseSlugUniqueResolver;

class SluggableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/sluggable.php', 'sluggable');

        $this->app->singleton(SlugGenerator::class, function () {
            return new DefaultSlugGenerator((string)config('sluggable.separator', '-'));
        });

        $this->app->singleton(SlugUniqueResolver::class, DatabaseSlugUniqueResolver::class);

        $this->app->singleton(SlugService::class, function ($app) {
            return new SlugService(
                $app->make(SlugGenerator::class),
                $app->make(SlugUniqueResolver::class)
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/sluggable.php' => config_path('sluggable.php'),
        ], 'sluggable-config');
    }
}
