<?php declare(strict_types=1);

namespace ImBrett\LaravelSluggable\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ImBrett\LaravelSluggable\Providers\SluggableServiceProvider;
use ImBrett\LaravelSluggable\Support\SlugManager;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SlugManager::reset();

        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        SlugManager::reset();

        parent::tearDown();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            SluggableServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('sluggable', require __DIR__ . '/../config/sluggable.php');
    }

    protected function setUpDatabase(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });
    }
}
