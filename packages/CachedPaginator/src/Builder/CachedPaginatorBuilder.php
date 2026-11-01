<?php declare(strict_types=1);

namespace ImBrett\LaravelCachedPaginator\Builder;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use ImBrett\LaravelCachedPaginator\Support\CacheKeyResolver;

/**
 * @template TModel of Model
 * @extends Builder<TModel>
 * @method $this filter(array<int, string>|null $filters)
 * @method $this search(?string $keyword, array<int, string>|null $columns = [], array<string, array<int, string>> $relations = [])
 * @method $this sort(\ImBrett\LaravelApiQuery\Data\SortData $data)
 */
class CachedPaginatorBuilder extends Builder
{
    /**
     * @param  array<int, string>  $columns
     * @param  int|null  $total
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
    {
        if ($total !== null) {
            return parent::paginate($perPage, $columns, $pageName, $page, $total);
        }

        [$page, $perPage] = $this->resolvePaginationInputs($perPage, $pageName, $page);
        $total = $this->calculateTotalCount();
        $results = $this->resolveResults($total, $page, $perPage, $columns);

        return $this->buildPaginator($results, $total, $perPage, $page, $pageName);
    }

    /**
     * @param  (\Closure(int): int)|int|string|null  $perPage
     * @return array{0: int, 1: int}
     */
    protected function resolvePaginationInputs(\Closure|int|string|null $perPage, string $pageName, int|string|null $page): array
    {
        $currentPage = $page ?: Paginator::resolveCurrentPage($pageName);

        if ($perPage instanceof \Closure) {
            $perPage = $perPage($this->model->getPerPage());
        }

        $perPage = $perPage ?: $this->model->getPerPage();

        return [(int)$currentPage, (int)$perPage];
    }

    /**
     * @param  array<int, string>  $columns
     * @return EloquentCollection<int, TModel>
     */
    protected function resolveResults(int $total, int $page, int $perPage, array $columns): EloquentCollection
    {
        if ($total <= 0) {
            /** @var EloquentCollection<int, TModel> $collection */
            $collection = $this->model->newCollection();

            return $collection;
        }

        /** @var EloquentCollection<int, TModel> $collection */
        $collection = $this->forPage($page, $perPage)->get($columns);

        return $collection;
    }

    /**
     * @param  EloquentCollection<int, TModel>  $results
     * @return LengthAwarePaginator<int, TModel>
     */
    protected function buildPaginator(
        EloquentCollection $results,
        int $total,
        int $perPage,
        int $page,
        string $pageName
    ): LengthAwarePaginator {
        return $this->paginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }
    protected function calculateTotalCount(): int
    {
        $ttl = $this->resolveTtl();
        $threshold = $this->resolveThreshold();

        return $this->shouldCacheTotals($ttl)
            ? $this->cachedCount($ttl, $threshold)
            : $this->countForPagination();
    }

    protected function shouldCacheTotals(int $ttl): bool
    {
        return $ttl > 0;
    }

    protected function cachedCount(int $ttl, int $threshold): int
    {
        $key = $this->makeCacheKey();

        $cached = Cache::get($key);
        if (is_int($cached)) {
            return $cached;
        }

        $total = $this->countForPagination();

        if ($total >= $threshold) {
            Cache::put($key, $total, $ttl);
        }

        return $total;
    }

    protected function countForPagination(): int
    {
        return $this->toBase()->getCountForPagination();
    }

    protected function makeCacheKey(): string
    {
        $prefix = $this->resolvePrefix();

        $hash = app(CacheKeyResolver::class)->resolve($this->query);

        $table = $this->getModel()->getTable();

        return implode(':', array_filter([$prefix, $table, $hash]));
    }

    protected function resolvePrefix(): string
    {
        $modelPrefix = $this->callModelMethod('cachedPaginationPrefix');

        return (string)($modelPrefix ?? config('cached-paginator.prefix'));
    }

    protected function resolveTtl(): int
    {
        $ttl = $this->callModelMethod('cachedPaginationTtl');

        return $this->normalisePositiveInt($ttl, (int)config('cached-paginator.ttl', 0));
    }

    protected function resolveThreshold(): int
    {
        $threshold = $this->callModelMethod('cachedPaginationThreshold');

        return $this->normalisePositiveInt($threshold, (int)config('cached-paginator.threshold', 0));
    }

    protected function callModelMethod(string $method): mixed
    {
        return method_exists($this->model, $method) ? $this->model->{$method}() : null;
    }

    protected function normalisePositiveInt(int|string|null $value, int $fallback): int
    {
        return max(0, (int)($value ?? $fallback));
    }
}
