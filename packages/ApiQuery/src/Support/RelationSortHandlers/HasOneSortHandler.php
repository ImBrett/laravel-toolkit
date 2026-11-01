<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Support\RelationSortHandlers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use ImBrett\LaravelApiQuery\Support\Contracts\RelationSortHandler;

class HasOneSortHandler implements RelationSortHandler
{
    public function supports(Relation $relation): bool
    {
        return $relation instanceof HasOne;
    }

    public function apply(Builder $query, Relation $relation, string $column, string $direction): Builder
    {
        /** @var HasOne $relation */
        $related = $relation->getRelated();
        $parent = $relation->getParent();

        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        /** @var Builder<Model> $builder */
        $builder = $query->leftJoin(
            $related->getTable(),
            $relation->getQualifiedForeignKeyName(),
            '=',
            $relation->getQualifiedParentKeyName()
        )->select($parent->getTable() . '.*')
            ->orderBy("{$related->getTable()}.{$column}", $direction);

        return $builder;
    }
}
