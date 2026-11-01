<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ImBrett\LaravelTemporal\Providers\TemporalServiceProvider;
use ImBrett\LaravelTemporal\Tests\Support\AccountBalance;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'temporal.infinity' => '9999-12-31 23:59:59',
            'temporal.user_resolver' => static fn () => 'tester@example.com',
        ]);

        $this->setUpDatabase();

        // Ensure resolving the temporal model from the container yields a fresh instance.
        app()->bind(AccountBalance::class, fn () => new AccountBalance());
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('account_balances');
        Schema::dropIfExists('accounts');

        if (class_exists(\Mockery::class)) {
            \Mockery::close();
        }

        parent::tearDown();
    }
    protected function getPackageProviders($app)
    {
        return [
            TemporalServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUpDatabase(): void
    {
        Schema::dropIfExists('account_balances');
        Schema::dropIfExists('accounts');

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('balance', 18, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('account_balances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->decimal('amount', 18, 4)->nullable();
            $table->string('source')->nullable();
            // @phpstan-ignore-next-line Blueprint macro registered via service provider
            $table->temporalColumns();
            // @phpstan-ignore-next-line Blueprint macro registered via service provider
            $table->temporalIndexes('account_id');
        });
    }
}
