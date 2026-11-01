<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ImBrett\LaravelApiQuery\Providers\ApiQueryServiceProvider;
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
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('authors');

        parent::tearDown();
    }
    protected function getPackageProviders($app)
    {
        return [
            ApiQueryServiceProvider::class,
        ];
    }

    protected function setUpDatabase(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create('authors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('author_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('title');
            $table->text('body')->nullable();
            $table->integer('views')->default(0);
            $table->timestamps();
        });
    }
}
