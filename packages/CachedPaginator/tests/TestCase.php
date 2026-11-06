<?php declare(strict_types=1);

namespace ImBrett\LaravelCachedPaginator\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use ImBrett\LaravelCachedPaginator\Providers\CachedPaginatorServiceProvider;
use ImBrett\LaravelCachedPaginator\Tests\Support\Post;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $this->setUpDatabase();

        app()->bind(Post::class, fn () => new Post());
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('posts');
        Cache::clearResolvedInstance('cache');

        parent::tearDown();
    }
    protected function getPackageProviders($app)
    {
        return [
            CachedPaginatorServiceProvider::class,
        ];
    }

    protected function setUpDatabase(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });
    }
}
