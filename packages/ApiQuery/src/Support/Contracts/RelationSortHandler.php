<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Support\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

interface RelationSortHandler
{
    public function supports(Relation $relation): bool;

    /**
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    public function apply(Builder $query, Relation $relation, string $column, string $direction): Builder;
}
