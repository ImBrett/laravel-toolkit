<?php declare(strict_types=1);

namespace ImBrett\LaravelCachedPaginator\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

class CacheKeyResolver
{
    /**
     * @param EloquentBuilder<Model>|QueryBuilder $query
     */
    public function resolve(EloquentBuilder|QueryBuilder $query): string
    {
        $base = $query instanceof EloquentBuilder ? $query->toBase() : $query;

        $sql = $base->toSql();
        $bindings = $base->getBindings();

        return md5($sql . '|' . json_encode($bindings));
    }
}
