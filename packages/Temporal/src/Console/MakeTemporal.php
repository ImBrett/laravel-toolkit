<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ImBrett\LaravelTemporal\Console\Support\TemporalScaffolder;

class MakeTemporal extends Command
{
    protected $signature = 'make:temporal {name} {model} {--columns=}';
    protected $description = 'Scaffold a temporal model and migration';

    public function handle(): int
    {
        $input = $this->prepareInput();

        if ($input === null) {
            return Command::FAILURE;
        }

        [$name, $model, $columns] = $input;

        if (!$this->scaffolder()->generate($name, $model, $columns)) {
            $this->error('Unable to scaffold temporal resources.');

            return Command::FAILURE;
        }

        $this->info(sprintf(
            'Created model app/Models/%s.php and migration for %s',
            $name,
            $model->getTable()
        ));

        return Command::SUCCESS;
    }

    /**
     * @return array{string, Model, array<int, string>}|null
     */
    protected function prepareInput(): ?array
    {
        $nameArg = $this->argument('name');
        $modelArg = $this->argument('model');

        if (!is_string($nameArg) || !is_string($modelArg)) {
            $this->error('The name and model arguments must be strings.');

            return null;
        }

        $model = app($modelArg);

        if (!$model instanceof Model) {
            $this->error('The provided model must be an Eloquent model class.');

            return null;
        }

        return [
            Str::studly($nameArg),
            $model,
            $this->parseColumns($this->option('columns')),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function parseColumns(mixed $columnsOption): array
    {
        if (!is_string($columnsOption) && !is_array($columnsOption) && !is_bool($columnsOption) && $columnsOption !== null) {
            $columnsOption = null;
        }

        $columns = match (true) {
            is_array($columnsOption) => array_map(
                trim(...),
                array_filter($columnsOption, static fn ($column): bool => is_string($column) && $column !== '')
            ),
            is_string($columnsOption) && $columnsOption !== '' => array_map(trim(...), explode(',', $columnsOption)),
            default => [],
        };

        return array_values(array_filter(
            $columns,
            static fn (string $column): bool => $column !== ''
        ));
    }

    protected function scaffolder(): TemporalScaffolder
    {
        return app(TemporalScaffolder::class);
    }
}
