<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Providers;

use Illuminate\Support\ServiceProvider;
use ImBrett\LaravelTemporal\Console\MakeTemporal;
use ImBrett\LaravelTemporal\Console\Support\TemporalScaffolder;
use ImBrett\LaravelTemporal\Support\BlueprintMacros;
use ImBrett\LaravelTemporal\Support\TemporalComparatorRegistry;

class TemporalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->packagePath('config/temporal.php'), 'temporal');

        $this->app->singleton(TemporalScaffolder::class);
        $this->app->singleton(TemporalComparatorRegistry::class);
    }

    public function boot(): void
    {
        // Publish config & stubs
        $this->publishes([
            $this->packagePath('config/temporal.php') => config_path('temporal.php'),
        ], 'temporal-config');

        $this->publishes([
            $this->packagePath('stubs/temporal-model.stub') => base_path('stubs/temporal-model.stub'),
            $this->packagePath('stubs/temporal-migration.stub') => base_path('stubs/temporal-migration.stub'),
        ], 'temporal-stubs');

        // Register console command
        if ($this->app->runningInConsole()) {
            $this->commands([MakeTemporal::class]);
        }

        // Register schema macros
        BlueprintMacros::register();
    }

    private function packagePath(string $path): string
    {
        return dirname(__DIR__, 2) . '/' . ltrim($path, '/');
    }
}
