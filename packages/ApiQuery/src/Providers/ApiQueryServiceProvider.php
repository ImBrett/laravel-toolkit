<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Providers;

use Illuminate\Support\ServiceProvider;
use ImBrett\LaravelApiQuery\Support\FilterOperatorManager;
use ImBrett\LaravelApiQuery\Support\RelationSortHandlerManager;
use ImBrett\LaravelApiQuery\Support\RelationSortHandlers\BelongsToSortHandler;
use ImBrett\LaravelApiQuery\Support\RelationSortHandlers\HasOneSortHandler;

class ApiQueryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FilterOperatorManager::class);
        $this->app->singleton(RelationSortHandlerManager::class, function () {
            $manager = new RelationSortHandlerManager();
            $manager->extend(new BelongsToSortHandler());
            $manager->extend(new HasOneSortHandler());

            return $manager;
        });
    }
}
