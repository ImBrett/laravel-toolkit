<?php declare(strict_types=1);

namespace ImBrett\LaravelCachedPaginator\Tests\Unit;

use ImBrett\LaravelCachedPaginator\Builder\CachedPaginatorBuilder;
use ImBrett\LaravelCachedPaginator\Tests\Support\Post;
use ImBrett\LaravelCachedPaginator\Tests\TestCase;

final class UsesCachedPaginationTest extends TestCase
{
    public function testNewEloquentBuilderReturnsCachedPaginatorBuilder(): void
    {
        $model = new Post();
        $builder = $model->newEloquentBuilder($model->newQuery()->getQuery());

        $this->assertInstanceOf(CachedPaginatorBuilder::class, $builder);
    }

    public function testCachedPaginationConfigurationMethodsRespectOverrides(): void
    {
        $base = new Post();
        $this->assertNull($base->cachedPaginationTtl());
        $this->assertNull($base->cachedPaginationPrefix());
        $this->assertNull($base->cachedPaginationThreshold());

        $configured = new class() extends Post {
            protected ?int $cachedPaginationTtl = 120;
            protected ?string $cachedPaginationPrefix = 'custom-prefix';
            protected ?int $cachedPaginationThreshold = 500;
        };

        $this->assertSame(120, $configured->cachedPaginationTtl());
        $this->assertSame('custom-prefix', $configured->cachedPaginationPrefix());
        $this->assertSame(500, $configured->cachedPaginationThreshold());
    }
}
