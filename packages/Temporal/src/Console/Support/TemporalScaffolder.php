<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Console\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TemporalScaffolder
{
    /**
     * @param  array<int, string>  $columns
     */
    public function generate(string $name, Model $model, array $columns): bool
    {
        if (!$this->writeModel($name, $model, $columns)) {
            return false;
        }

        return $this->writeMigration($model, $columns);
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function writeModel(string $name, Model $model, array $columns): bool
    {
        $modelStub = $this->readStub('temporal-model.stub');
        if ($modelStub === false) {
            return false;
        }

        $contents = strtr(
            $modelStub,
            [
                '{{ class }}' => $name,
                '{{ table }}' => $model->getTable(),
                '{{ columns }}' => $this->formatColumns($columns),
                '{{ foreignKey }}' => $model->getForeignKey(),
            ]
        );

        return $this->putFile(
            app_path(sprintf('Models/%s.php', $name)),
            $contents
        );
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function writeMigration(Model $model, array $columns): bool
    {
        $migrationStub = $this->readStub('temporal-migration.stub');
        if ($migrationStub === false) {
            return false;
        }

        $migration = strtr(
            $migrationStub,
            [
                '{{ table }}' => $model->getTable(),
                '{{ model }}' => '\\' . ltrim($model::class, '\\'),
                '{{ columns_migration }}' => $this->columnsMigration($columns),
                '{{ foreignKey }}' => $model->getForeignKey(),
                '{{ class }}' => 'Create' . Str::studly($model->getTable()) . 'Table',
            ]
        );

        return $this->putFile(
            database_path(sprintf(
                'migrations/%s_create_%s_table.php',
                now()->format('Y_m_d_His'),
                $model->getTable()
            )),
            $migration
        );
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function columnsMigration(array $columns): string
    {
        $columns = array_values(array_filter(
            array_map(trim(...), $columns),
            static fn (string $column): bool => $column !== ''
        ));

        if ($columns === []) {
            return '';
        }

        return implode(
            PHP_EOL,
            array_map(
                static fn (string $column): string => "            \$table->decimal('{$column}', 18, 4)->nullable();",
                $columns
            )
        ) . PHP_EOL;
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function formatColumns(array $columns): string
    {
        if ($columns === []) {
            return '[]';
        }

        $items = array_map(
            static fn (string $column): string => "'" . addslashes($column) . "'",
            $columns
        );

        return '[' . implode(', ', $items) . ']';
    }

    protected function putFile(string $path, string $contents): bool
    {
        $directory = dirname($path);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                return false;
            }
        }

        return file_put_contents($path, $contents) !== false;
    }

    private function readStub(string $file): string|false
    {
        $stubPath = base_path('stubs/' . ltrim($file, '/'));

        if (!file_exists($stubPath)) {
            $fallback = $this->packagePath('stubs/' . ltrim($file, '/'));
            $stubPath = file_exists($fallback) ? $fallback : $stubPath;
        }

        return file_get_contents($stubPath);
    }

    private function packagePath(string $path): string
    {
        return dirname(__DIR__, 2) . '/' . ltrim($path, '/');
    }
}
