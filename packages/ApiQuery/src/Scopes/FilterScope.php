<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use ImBrett\LaravelApiQuery\Support\FilterOperatorManager;
use InvalidArgumentException;

use function throw_if;

trait FilterScope
{
    /**
     * @param  Builder<Model>  $query
     * @param  array<int, string>|null  $filters
     * @return Builder<Model>
     */
    public function scopeFilter(Builder $query, ?array $filters): Builder
    {
        if (empty($filters)) {
            return $query;
        }

        $manager = app(FilterOperatorManager::class);

        $parsed = array_map(function (string $raw) use ($manager): array {
            $filter = str_replace('&filters[]=', '', $raw);

            /** @var non-empty-string $operator */
            $operator = $manager->detectOperator($filter);

            [$relationPath, $value] = explode($operator, $filter, 2);

            $segments = $relationPath === '' ? [''] : explode('.', $relationPath);
            $fieldValue = array_pop($segments);
            $field = is_string($fieldValue) ? $fieldValue : '';

            throw_if(
                $field === '',
                InvalidArgumentException::class,
                "Unable to resolve field from filter [{$raw}]."
            );

            $relation = implode('.', array_filter($segments, static fn (string $segment): bool => $segment !== ''));

            return [
                'relation' => $relation,
                'field' => $field,
                'operator' => $operator,
                'value' => $value,
            ];
        }, $filters);

        foreach ($parsed as $filter) {
            $query = $this->applyFilter($query, $filter, $manager);
        }

        return $query;
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array{relation:string, field:string, operator:string, value:string}  $filter
     * @return Builder<Model>
     */
    protected function applyFilter(Builder $query, array $filter, FilterOperatorManager $manager): Builder
    {
        return $filter['relation'] === ''
            ? $this->applyFilterToBuilder($query, $filter, $manager)
            : $query->whereHas($filter['relation'], function (Builder $relationQuery) use ($filter, $manager): void {
                $this->applyFilterToBuilder($relationQuery, $filter, $manager);
            });
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array{field:string, operator:string, value:string}  $filter
     * @return Builder<Model>
     */
    protected function applyFilterToBuilder(Builder $query, array $filter, FilterOperatorManager $manager): Builder
    {
        return $manager->apply($query, $filter['operator'], $filter['field'], $filter['value']);
    }
}
