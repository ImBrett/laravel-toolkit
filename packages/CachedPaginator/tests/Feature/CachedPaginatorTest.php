<?php declare(strict_types=1);

namespace ImBrett\LaravelCachedPaginator\Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use ImBrett\LaravelCachedPaginator\Support\CacheKeyResolver;
use ImBrett\LaravelCachedPaginator\Tests\Support\Post;
use ImBrett\LaravelCachedPaginator\Tests\TestCase;
use Mockery;

final class CachedPaginatorTest extends TestCase
{
    public function testItCachesTotalCountsAndReusesThem(): void
    {
        config()->set('cached-paginator.ttl', 60);
        config()->set('cached-paginator.threshold', 0);
        config()->set('cached-paginator.prefix', 'pagination');

        Post::query()->insert(array_map(
            fn (int $i) => [
                'title' => 'Post ' . $i,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            range(1, 30)
        ));

        Cache::shouldReceive('get')
            ->twice()
            ->with(Mockery::type('string'))
            ->andReturn(null, 30);

        Cache::shouldReceive('put')
            ->once()
            ->with(Mockery::type('string'), 30, 60);

        $first = Post::query()->paginate(10);
        $second = Post::query()->paginate(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $first);
        $this->assertSame(30, $first->total());
        $this->assertSame(30, $second->total());
    }

    public function testThresholdPreventsCachingWhenTotalIsSmall(): void
    {
        config()->set('cached-paginator.ttl', 60);
        config()->set('cached-paginator.threshold', 100);

        Post::create([
            'title' => 'Single',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::shouldReceive('get')
            ->once()
            ->with(Mockery::type('string'))
            ->andReturn(null);

        Cache::shouldReceive('put')->never();

        $paginator = Post::query()->paginate(10);

        $this->assertSame(1, $paginator->total());
    }

    public function testZeroTtlDisablesCachedPagination(): void
    {
        config()->set('cached-paginator.ttl', 0);
        config()->set('cached-paginator.threshold', 0);

        Post::create([
            'title' => 'No cache',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::shouldReceive('get')->never();
        Cache::shouldReceive('put')->never();

        $paginator = Post::query()->paginate(10);

        $this->assertSame(1, $paginator->total());
    }

    public function testCustomCacheKeyResolverCanBeBound(): void
    {
        config()->set('cached-paginator.ttl', 60);
        config()->set('cached-paginator.threshold', 0);

        $resolvedKey = 'pagination:posts:custom-key';

        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        $app->bind(CacheKeyResolver::class, function () {
            return new class() extends CacheKeyResolver {
                public function resolve(\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query): string
                {
                    return 'custom-key';
                }
            };
        });

        Post::query()->insert(array_map(
            fn (int $i) => [
                'title' => 'Post ' . $i,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            range(1, 5)
        ));

        Cache::shouldReceive('get')
            ->once()
            ->with($resolvedKey)
            ->andReturn(null);

        Cache::shouldReceive('put')
            ->once()
            ->with($resolvedKey, 5, 60);

        Post::query()->paginate(5);
    }
}
