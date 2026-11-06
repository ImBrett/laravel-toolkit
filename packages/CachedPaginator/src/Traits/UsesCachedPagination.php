<?php declare(strict_types=1);

namespace ImBrett\LaravelCachedPaginator\Traits;

use Illuminate\Database\Query\Builder as BaseBuilder;
use ImBrett\LaravelCachedPaginator\Builder\CachedPaginatorBuilder;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model = \Illuminate\Database\Eloquent\Model
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait UsesCachedPagination
{
    /**
     * @param  BaseBuilder  $query
     * @return CachedPaginatorBuilder<TModel>
     */
    public function newEloquentBuilder($query): CachedPaginatorBuilder
    {
        return new CachedPaginatorBuilder($query);
    }

    public function cachedPaginationTtl(): ?int
    {
        return property_exists($this, 'cachedPaginationTtl')
            ? ($this->cachedPaginationTtl ?? null)
            : null;
    }

    public function cachedPaginationPrefix(): ?string
    {
        return property_exists($this, 'cachedPaginationPrefix')
            ? ($this->cachedPaginationPrefix ?? null)
            : null;
    }

    public function cachedPaginationThreshold(): ?int
    {
        return property_exists($this, 'cachedPaginationThreshold')
            ? ($this->cachedPaginationThreshold ?? null)
            : null;
    }
}
