<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Tests\Support;

use ImBrett\LaravelTemporal\Models\TemporalModel;

/**
 * @property int $account_id
 * @property string|null $amount
 * @property string|null $source
 * @property \Carbon\CarbonImmutable|null $valid_to
 * @property \Carbon\CarbonImmutable|null $valid_from
 * @property \Carbon\CarbonImmutable|null $tx_at
 */
class AccountBalance extends TemporalModel
{
    protected $table = 'account_balances';

    protected $fillable = [
        'account_id',
        'amount',
        'valid_from',
        'valid_to',
        'tx_at',
        'source',
    ];

    public function temporalColumns(): array
    {
        return ['amount'];
    }

    public function temporalForeignKey(): string
    {
        return 'account_id';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'valid_from' => 'immutable_datetime',
            'valid_to' => 'immutable_datetime',
            'tx_at' => 'immutable_datetime',
        ];
    }
}
