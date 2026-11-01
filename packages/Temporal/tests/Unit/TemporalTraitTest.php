<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Tests\Unit;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use ImBrett\LaravelTemporal\Support\TemporalComparatorRegistry;
use ImBrett\LaravelTemporal\Tests\Support\AccountBalance;
use ImBrett\LaravelTemporal\Tests\TestCase;

final class TemporalTraitTest extends TestCase
{
    public function testCurrentScopeFiltersRecordsByInfinityMarker(): void
    {
        $infinity = config('temporal.infinity');

        AccountBalance::create([
            'account_id' => 1,
            'amount' => '100.0000',
            'valid_from' => CarbonImmutable::parse('2024-01-01'),
            'valid_to' => $infinity,
            'tx_at' => CarbonImmutable::parse('2024-01-01'),
            'source' => 'seed',
        ]);

        AccountBalance::create([
            'account_id' => 1,
            'amount' => '50.0000',
            'valid_from' => CarbonImmutable::parse('2023-01-01'),
            'valid_to' => CarbonImmutable::parse('2023-12-31'),
            'tx_at' => CarbonImmutable::parse('2023-01-01'),
            'source' => 'seed',
        ]);

        $currentIds = AccountBalance::query()->current()->pluck('amount')->all();

        $this->assertSame(['100.0000'], $currentIds);
    }

    public function testAsOfScopeFindsRecordValidAtSpecificMoment(): void
    {
        $infinity = config('temporal.infinity');

        AccountBalance::insert([
            [
                'account_id' => 1,
                'amount' => '25.0000',
                'valid_from' => CarbonImmutable::parse('2023-01-01'),
                'valid_to' => CarbonImmutable::parse('2023-06-30'),
                'tx_at' => CarbonImmutable::parse('2023-01-01'),
                'source' => 'fixture',
            ],
            [
                'account_id' => 1,
                'amount' => '40.0000',
                'valid_from' => CarbonImmutable::parse('2023-07-01'),
                'valid_to' => $infinity,
                'tx_at' => CarbonImmutable::parse('2023-07-01'),
                'source' => 'fixture',
            ],
        ]);

        $asOf = AccountBalance::query()
            ->asOf('2023-08-15 12:00:00')
            ->pluck('amount')
            ->all();

        $this->assertSame(['40.0000'], $asOf);
    }

    public function testPastScopeReturnsHistoricalRows(): void
    {
        $infinity = config('temporal.infinity');
        CarbonImmutable::setTestNow('2024-01-10 00:00:00');

        try {
            AccountBalance::insert([
                [
                    'account_id' => 1,
                    'amount' => '10.0000',
                    'valid_from' => CarbonImmutable::parse('2023-12-01'),
                    'valid_to' => CarbonImmutable::parse('2024-01-05'),
                    'tx_at' => CarbonImmutable::parse('2023-12-01'),
                    'source' => 'seed',
                ],
                [
                    'account_id' => 1,
                    'amount' => '20.0000',
                    'valid_from' => CarbonImmutable::parse('2024-01-05'),
                    'valid_to' => $infinity,
                    'tx_at' => CarbonImmutable::parse('2024-01-05'),
                    'source' => 'seed',
                ],
            ]);

            $past = AccountBalance::query()->past()->pluck('amount')->all();

            $this->assertSame(['10.0000'], $past);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function testFutureScopeReturnsUpcomingRows(): void
    {
        $infinity = config('temporal.infinity');
        CarbonImmutable::setTestNow('2024-01-10 00:00:00');

        try {
            AccountBalance::insert([
                [
                    'account_id' => 1,
                    'amount' => '10.0000',
                    'valid_from' => CarbonImmutable::parse('2023-12-01'),
                    'valid_to' => CarbonImmutable::parse('2023-12-31'),
                    'tx_at' => CarbonImmutable::parse('2023-12-01'),
                    'source' => 'seed',
                ],
                [
                    'account_id' => 1,
                    'amount' => '30.0000',
                    'valid_from' => CarbonImmutable::parse('2024-02-01'),
                    'valid_to' => $infinity,
                    'tx_at' => CarbonImmutable::parse('2024-02-01'),
                    'source' => 'seed',
                ],
            ]);

            $future = AccountBalance::query()->future()->pluck('amount')->all();

            $this->assertSame(['30.0000'], $future);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function testOverlappingScopeMatchesIntervals(): void
    {
        AccountBalance::insert([
            [
                'account_id' => 1,
                'amount' => '10.0000',
                'valid_from' => CarbonImmutable::parse('2024-01-01'),
                'valid_to' => CarbonImmutable::parse('2024-01-31'),
                'tx_at' => CarbonImmutable::parse('2024-01-01'),
                'source' => 'seed',
            ],
            [
                'account_id' => 1,
                'amount' => '20.0000',
                'valid_from' => CarbonImmutable::parse('2024-02-01'),
                'valid_to' => CarbonImmutable::parse('2024-02-28'),
                'tx_at' => CarbonImmutable::parse('2024-02-01'),
                'source' => 'seed',
            ],
        ]);

        $results = AccountBalance::query()
            ->overlapping('2024-01-10', '2024-01-20')
            ->pluck('amount')
            ->all();

        $this->assertSame(['10.0000'], $results);
    }

    public function testContainedWithinScopeRestrictsToInnerIntervals(): void
    {
        AccountBalance::insert([
            [
                'account_id' => 1,
                'amount' => '10.0000',
                'valid_from' => CarbonImmutable::parse('2024-01-01'),
                'valid_to' => CarbonImmutable::parse('2024-01-31'),
                'tx_at' => CarbonImmutable::parse('2024-01-01'),
                'source' => 'seed',
            ],
            [
                'account_id' => 1,
                'amount' => '20.0000',
                'valid_from' => CarbonImmutable::parse('2024-02-01'),
                'valid_to' => CarbonImmutable::parse('2024-02-28'),
                'tx_at' => CarbonImmutable::parse('2024-02-01'),
                'source' => 'seed',
            ],
        ]);

        $results = AccountBalance::query()
            ->containedWithin('2024-01-01', '2024-01-31')
            ->pluck('amount')
            ->all();

        $this->assertSame(['10.0000'], $results);
    }

    public function testCoveringScopeFindsRowsSpanningInterval(): void
    {
        AccountBalance::insert([
            [
                'account_id' => 1,
                'amount' => '10.0000',
                'valid_from' => CarbonImmutable::parse('2024-01-01'),
                'valid_to' => CarbonImmutable::parse('2024-01-31'),
                'tx_at' => CarbonImmutable::parse('2024-01-01'),
                'source' => 'seed',
            ],
            [
                'account_id' => 1,
                'amount' => '20.0000',
                'valid_from' => CarbonImmutable::parse('2024-02-01'),
                'valid_to' => CarbonImmutable::parse('2024-02-28'),
                'tx_at' => CarbonImmutable::parse('2024-02-01'),
                'source' => 'seed',
            ],
        ]);

        $results = AccountBalance::query()
            ->covering('2024-01-05', '2024-01-25')
            ->pluck('amount')
            ->all();

        $this->assertSame(['10.0000'], $results);
    }

    public function testChangedSinceScopeUsesTransactionTimestamp(): void
    {
        AccountBalance::insert([
            [
                'account_id' => 1,
                'amount' => '10.0000',
                'valid_from' => CarbonImmutable::parse('2024-01-01'),
                'valid_to' => CarbonImmutable::parse('2024-01-31'),
                'tx_at' => CarbonImmutable::parse('2024-01-01'),
                'source' => 'seed',
            ],
            [
                'account_id' => 1,
                'amount' => '20.0000',
                'valid_from' => CarbonImmutable::parse('2024-02-01'),
                'valid_to' => CarbonImmutable::parse('2024-02-28'),
                'tx_at' => CarbonImmutable::parse('2024-02-02'),
                'source' => 'seed',
            ],
        ]);

        $results = AccountBalance::query()
            ->changedSince('2024-02-01')
            ->pluck('amount')
            ->all();

        $this->assertSame(['20.0000'], $results);
    }

    public function testOpenEndedScopeReturnsInfinityRows(): void
    {
        $infinity = config('temporal.infinity');

        AccountBalance::insert([
            [
                'account_id' => 1,
                'amount' => '10.0000',
                'valid_from' => CarbonImmutable::parse('2024-01-01'),
                'valid_to' => $infinity,
                'tx_at' => CarbonImmutable::parse('2024-01-01'),
                'source' => 'seed',
            ],
            [
                'account_id' => 1,
                'amount' => '20.0000',
                'valid_from' => CarbonImmutable::parse('2023-01-01'),
                'valid_to' => CarbonImmutable::parse('2023-12-31'),
                'tx_at' => CarbonImmutable::parse('2023-01-01'),
                'source' => 'seed',
            ],
        ]);

        $results = AccountBalance::query()->openEnded()->pluck('amount')->all();

        $this->assertSame(['10.0000'], $results);
    }

    public function testClosedScopeReturnsFiniteRows(): void
    {
        $infinity = config('temporal.infinity');

        AccountBalance::insert([
            [
                'account_id' => 1,
                'amount' => '10.0000',
                'valid_from' => CarbonImmutable::parse('2024-01-01'),
                'valid_to' => $infinity,
                'tx_at' => CarbonImmutable::parse('2024-01-01'),
                'source' => 'seed',
            ],
            [
                'account_id' => 1,
                'amount' => '20.0000',
                'valid_from' => CarbonImmutable::parse('2023-01-01'),
                'valid_to' => CarbonImmutable::parse('2023-12-31'),
                'tx_at' => CarbonImmutable::parse('2023-01-01'),
                'source' => 'seed',
            ],
        ]);

        $results = AccountBalance::query()->closed()->pluck('amount')->all();

        $this->assertSame(['20.0000'], $results);
    }

    public function testForAnchorScopeFiltersByForeignKey(): void
    {
        $infinity = config('temporal.infinity');

        AccountBalance::insert([
            [
                'account_id' => 1,
                'amount' => '10.0000',
                'valid_from' => CarbonImmutable::parse('2024-01-01'),
                'valid_to' => $infinity,
                'tx_at' => CarbonImmutable::parse('2024-01-01'),
                'source' => 'seed',
            ],
            [
                'account_id' => 2,
                'amount' => '20.0000',
                'valid_from' => CarbonImmutable::parse('2023-01-01'),
                'valid_to' => $infinity,
                'tx_at' => CarbonImmutable::parse('2023-01-01'),
                'source' => 'seed',
            ],
        ]);

        $results = AccountBalance::query()->forAnchor(2)->pluck('amount')->all();

        $this->assertSame(['20.0000'], $results);
    }

    public function testUpsertTemporalClosesCurrentRecordAndCreatesFreshVersion(): void
    {
        $infinity = config('temporal.infinity');

        // First write creates a temporal record
        /** @var AccountBalance $first */
        $first = (new AccountBalance())->upsertTemporal(
            anchorId: 1,
            attributes: [
                'amount' => '100.0000',
            ],
            source: 'initial'
        );

        $this->assertDatabaseHas('account_balances', [
            'id' => $first->id,
            'account_id' => 1,
            'amount' => '100.0000',
            'valid_to' => $infinity,
            'source' => 'initial',
        ]);

        // No changes should be made when the attributes are identical
        /** @var AccountBalance $second */
        $second = (new AccountBalance())->upsertTemporal(
            anchorId: 1,
            attributes: [
                'amount' => '100.0000',
            ],
            source: 'duplicate'
        );

        $this->assertTrue($first->is($second));
        $this->assertSame(1, AccountBalance::count());

        // Changes trigger closing the current record and inserting a new one
        Carbon::setTestNow($expectedNow = now()->addHour());
        /** @var AccountBalance $third */
        $third = (new AccountBalance())->upsertTemporal(
            anchorId: 1,
            attributes: [
                'amount' => '125.0000',
            ],
            source: 'updated'
        );
        Carbon::setTestNow();

        $this->assertSame('125.0000', $third->amount);
        $this->assertSame('updated', $third->source);

        $thirdValidTo = $third->valid_to;
        $this->assertNotNull($thirdValidTo);
        $this->assertSame($infinity, $thirdValidTo->format('Y-m-d H:i:s'));

        $first->refresh();

        $firstValidTo = $first->valid_to;
        $this->assertNotNull($firstValidTo);
        $this->assertSame($expectedNow->format('Y-m-d H:i:s'), $firstValidTo->format('Y-m-d H:i:s'));
        $this->assertCount(2, AccountBalance::all());
    }

    public function testCustomComparatorCanShortCircuitUpdates(): void
    {
        /** @var TemporalComparatorRegistry $registry */
        $registry = app(TemporalComparatorRegistry::class);
        $registry->extend(AccountBalance::class, function (\ImBrett\LaravelTemporal\Models\TemporalModel $current, array $attributes): bool {
            if (!$current instanceof AccountBalance || !isset($attributes['amount'])) {
                return false;
            }

            return abs((float)$current->amount - (float)$attributes['amount']) < 0.5;
        });

        $first = (new AccountBalance())->upsertTemporal(1, [
            'amount' => '100.00',
        ], 'initial');

        $second = (new AccountBalance())->upsertTemporal(1, [
            'amount' => '100.20',
        ], 'insignificant');

        $this->assertTrue($first->is($second));
        $this->assertSame(1, AccountBalance::count());
    }
}
