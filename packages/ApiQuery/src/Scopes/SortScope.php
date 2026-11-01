<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use ImBrett\LaravelApiQuery\Data\SortData;
use ImBrett\LaravelApiQuery\Support\RelationSortHandlerManager;
use InvalidArgumentException;

use function throw_if;
use function throw_unless;

trait SortScope
{
    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeSort(Builder $query, SortData $data): Builder
    {
        $model = $query->getModel();
        $column = $data->column;

        if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $column)) {
            return $query->orderBy($model->qualifyColumn($column), $data->direction);
        }

        return $this->applyRelationSort($query, $data);
    }

    /**
     * @phpstan-ignore-next-line
     */
    protected function applyRelationSort(Builder $query, SortData $data): Builder
    {
        [$relationPath, $targetColumn] = $this->splitRelationColumn($data->column);

        $model = $query->getModel();
        $relation = $this->resolveRelation($model, $relationPath);

        $relatedTable = $relation->getRelated()->getTable();

        throw_unless(
            $relation->getRelated()->getConnection()->getSchemaBuilder()->hasColumn($relatedTable, $targetColumn),
            InvalidArgumentException::class,
            sprintf('Column [%s] does not exist on related table [%s].', $targetColumn, $relatedTable)
        );

        return app(RelationSortHandlerManager::class)->apply($query, $relation, $targetColumn, $data->direction);
    }

    protected function resolveRelation(Model $model, string $relationPath): Relation
    {
        $segments = explode('.', $relationPath);

        $currentModel = $model;
        $resolvedRelation = null;

        foreach ($segments as $segment) {
            throw_unless(
                method_exists($currentModel, $segment),
                InvalidArgumentException::class,
                sprintf('Relation [%s] is not defined on model %s.', $segment, $currentModel::class)
            );

            $resolvedRelation = $currentModel->{$segment}();

            throw_unless(
                $resolvedRelation instanceof Relation,
                InvalidArgumentException::class,
                sprintf('Method [%s] must return an Eloquent relation.', $segment)
            );

            $currentModel = $resolvedRelation->getRelated();
        }

        return $resolvedRelation;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function splitRelationColumn(string $column): array
    {
        $position = strrpos($column, '.');

        throw_if(
            $position === false,
            InvalidArgumentException::class,
            'Relation column must use dot notation (relation.column).'
        );

        $relation = substr($column, 0, $position);
        $field = substr($column, $position + 1);

        throw_if(
            $relation === '' || $field === '',
            InvalidArgumentException::class,
            'Relation column must include both relation and field segments.'
        );

        return [$relation, $field];
    }
}
