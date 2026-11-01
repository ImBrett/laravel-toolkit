<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Tests\Support;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use ImBrett\LaravelTemporal\Traits\HasTemporalAttributes;

/**
 * @property string|null $balance
 */
class Account extends Model
{
    use HasTemporalAttributes;

    protected $table = 'accounts';

    protected $fillable = [
        'name',
        'balance',
    ];

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function balance(): Attribute
    {
        return $this->temporalAttribute(AccountBalance::class, 'amount');
    }
}
