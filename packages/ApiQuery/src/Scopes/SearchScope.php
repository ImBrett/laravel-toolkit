<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait SearchScope
{
    /**
     * @param  Builder<Model>  $query
     * @param  array<int, string>|null  $columns
     * @param  array<string, array<int, string>>  $relations
     * @return Builder<Model>
     */
    public function scopeSearch(
        Builder $query,
        ?string $keyword,
        ?array $columns = [],
        array $relations = []
    ): Builder {
        if ($keyword === null || $keyword === '') {
            return $query;
        }

        $filter = str_replace('*', '%', $keyword);
        $model = $query->getModel();

        $columns = $this->resolveSearchableColumns($model, $columns);

        return $query->where(function (Builder $builder) use ($columns, $relations, $filter): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $builder->{$method}($column, 'LIKE', "%{$filter}%");
            }

            foreach ($relations as $relation => $relationColumns) {
                $builder->orWhereHas($relation, function (Builder $relationQuery) use (
                    $relation,
                    $relationColumns,
                    $filter
                ): void {
                    $relationTable = Str::snake(Arr::last(explode('.', $relation)) ?: $relation);
                    $qualifiedColumns = array_map(
                        fn (string $column) => Str::plural($relationTable) . '.' . $column,
                        $relationColumns
                    );

                    $relationQuery->where(function (Builder $nested) use ($qualifiedColumns, $filter): void {
                        foreach ($qualifiedColumns as $idx => $column) {
                            $method = $idx === 0 ? 'where' : 'orWhere';
                            $nested->{$method}($column, 'LIKE', "%{$filter}%");
                        }
                    });
                });
            }
        });
    }

    /**
     * @param  array<int, string>|null  $columns
     * @return array<int, string>
     */
    protected function resolveSearchableColumns(Model $model, ?array $columns): array
    {
        $columns ??= [];

        if (count($columns) === 0) {
            $columns = property_exists($model, 'searchable') && is_array($model->searchable)
                ? $model->searchable
                : Arr::wrap($model->getFillable());
        }

        $qualified = array_map(
            $model->qualifyColumn(...),
            $columns
        );

        return array_values(array_unique($qualified));
    }
}
