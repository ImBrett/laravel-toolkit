<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Models;

use Illuminate\Database\Eloquent\Model;
use ImBrett\LaravelTemporal\Contracts\TemporalContract;
use ImBrett\LaravelTemporal\Traits\Temporal;
use LogicException;

/**
 * @property int|null $id
 * @property \Carbon\CarbonImmutable|null $valid_from
 * @property \Carbon\CarbonImmutable|null $valid_to
 * @property \Carbon\CarbonImmutable|null $tx_at
 */
abstract class TemporalModel extends Model implements TemporalContract
{
    use Temporal;

    public $timestamps = false;
    protected $guarded = [];

    public function temporalForeignKey(): string
    {
        throw new LogicException('temporalForeignKey() must be implemented in subclass');
    }
    protected function casts(): array
    {
        return [
            'valid_from' => 'immutable_datetime',
            'valid_to' => 'immutable_datetime',
            'tx_at' => 'immutable_datetime',
        ];
    }
}
