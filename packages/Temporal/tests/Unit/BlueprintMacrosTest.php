<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ImBrett\LaravelTemporal\Models\TemporalModel;
use ImBrett\LaravelTemporal\Support\BlueprintMacros;
use ImBrett\LaravelTemporal\Tests\TestCase;
use LogicException;

final class BlueprintMacrosTest extends TestCase
{
    public function testMacrosAddExpectedColumnsAndIndexes(): void
    {
        BlueprintMacros::register();

        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            // @phpstan-ignore-next-line Blueprint macro registered via service provider
            $table->temporalColumns();
            // @phpstan-ignore-next-line Blueprint macro registered via service provider
            $table->temporalIndexes('user_id');
        });

        $columns = Schema::getColumnListing('reports');

        $this->assertContains('valid_from', $columns);
        $this->assertContains('valid_to', $columns);
        $this->assertContains('tx_at', $columns);

        $indexes = collect(DB::select("PRAGMA index_list('reports')"))
            ->pluck('name')
            ->all();

        $this->assertContains('reports_user_id_valid_to_index', $indexes);
        $this->assertContains('reports_user_id_valid_from_valid_to_index', $indexes);

        Schema::drop('reports');
    }

    public function testTemporalModelRequiresForeignKeyImplementation(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('temporalForeignKey() must be implemented in subclass');

        $model = new class() extends TemporalModel {
            protected $table = 'reports';

            public function temporalColumns(): array
            {
                return ['amount'];
            }
        };

        $model->temporalForeignKey();
    }
}
