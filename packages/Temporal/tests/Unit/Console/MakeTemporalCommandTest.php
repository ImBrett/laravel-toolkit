<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Tests\Unit\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use ImBrett\LaravelTemporal\Tests\Support\Account;
use ImBrett\LaravelTemporal\Tests\TestCase;

class MakeTemporalCommandTest extends TestCase
{
    public function testItScaffoldsModelAndMigrationFromStubs(): void
    {
        $filesystem = new Filesystem();
        $baseStubsPath = base_path('stubs');
        $filesystem->ensureDirectoryExists($baseStubsPath);

        $packageStubsPath = dirname(__DIR__, 3) . '/stubs';
        $modelStubPath = $baseStubsPath . '/temporal-model.stub';
        $migrationStubPath = $baseStubsPath . '/temporal-migration.stub';

        $originalStubContents = [];
        foreach ([$modelStubPath, $migrationStubPath] as $path) {
            if ($filesystem->exists($path)) {
                $originalStubContents[$path] = $filesystem->get($path);
            }
        }

        $filesystem->copy($packageStubsPath . '/temporal-model.stub', $modelStubPath);
        $filesystem->copy($packageStubsPath . '/temporal-migration.stub', $migrationStubPath);

        $modelPath = app_path('Models/AccountBalanceHistory.php');
        $modelDirectory = dirname($modelPath);
        $filesystem->ensureDirectoryExists($modelDirectory);

        $originalModelContents = $filesystem->exists($modelPath)
            ? $filesystem->get($modelPath)
            : null;

        $existingMigrations = glob(database_path('migrations/*_create_accounts_table.php')) ?: [];
        $createdMigrations = [];

        try {
            if ($filesystem->exists($modelPath)) {
                $filesystem->delete($modelPath);
            }

            Carbon::setTestNow('2024-01-01 12:00:00');

            $exitCode = Artisan::call('make:temporal', [
                'name' => 'AccountBalanceHistory',
                'model' => Account::class,
                '--columns' => 'amount,available_credit',
            ]);
            self::assertSame(Command::SUCCESS, $exitCode);

            self::assertFileExists($modelPath);
            $modelContents = $filesystem->get($modelPath);
            self::assertStringContainsString('class AccountBalanceHistory extends TemporalModel', $modelContents);
            self::assertStringContainsString("return ['amount', 'available_credit'];", $modelContents);
            self::assertStringContainsString("return 'account_id';", $modelContents);

            $allMigrations = glob(database_path('migrations/*_create_accounts_table.php')) ?: [];
            $createdMigrations = array_values(array_diff($allMigrations, $existingMigrations));

            self::assertCount(1, $createdMigrations);
            $migrationContents = $filesystem->get($createdMigrations[0]);
            self::assertStringContainsString('$table->foreignIdFor(\\' . Account::class . '::class)->constrained()->cascadeOnDelete();', $migrationContents);
            self::assertStringContainsString("\$table->decimal('amount', 18, 4)->nullable();", $migrationContents);
            self::assertStringContainsString("\$table->decimal('available_credit', 18, 4)->nullable();", $migrationContents);
            self::assertStringContainsString("\$table->temporalIndexes('account_id');", $migrationContents);
        } finally {
            Carbon::setTestNow(null);

            if ($originalModelContents !== null) {
                $filesystem->put($modelPath, $originalModelContents);
            } else {
                $filesystem->delete($modelPath);
            }

            foreach ($createdMigrations as $path) {
                $filesystem->delete($path);
            }

            foreach ([$modelStubPath, $migrationStubPath] as $path) {
                if (array_key_exists($path, $originalStubContents)) {
                    $filesystem->put($path, $originalStubContents[$path]);
                } else {
                    $filesystem->delete($path);
                }
            }
        }
    }
}
