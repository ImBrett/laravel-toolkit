<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Tests\Unit;

use ImBrett\LaravelTemporal\Tests\Support\Account;
use ImBrett\LaravelTemporal\Tests\Support\AccountBalance;
use ImBrett\LaravelTemporal\Tests\TestCase;

final class HasTemporalAttributesTest extends TestCase
{
    public function testReadingTemporalAttributeReturnsCurrentTemporalValue(): void
    {
        $account = Account::create([
            'name' => 'Primary',
        ]);

        (new AccountBalance())->upsertTemporal(
            anchorId: $account->id,
            attributes: [
                'amount' => '50.0000',
            ],
            source: 'initial'
        );

        $refreshed = $account->fresh();
        $this->assertNotNull($refreshed);
        $this->assertSame('50.0000', $refreshed->balance);
    }

    public function testSettingTemporalAttributeOnUnsavedModelDefersUntilPersisted(): void
    {
        $account = new Account([
            'name' => 'Deferred',
        ]);

        $account->balance = '20.0000';

        $this->assertSame(0, AccountBalance::count(), 'Temporal records should not be written before the model is saved.');

        $account->save();

        $balance = AccountBalance::where('account_id', $account->id)->current()->first();

        $this->assertNotNull($balance);
        $this->assertSame('20.0000', $balance->amount);
        $this->assertSame('tester@example.com', $balance->source);
        $refreshed = $account->fresh();
        $this->assertNotNull($refreshed);
        $this->assertSame('20.0000', $refreshed->balance);
    }

    public function testSettingTemporalAttributeOnPersistedModelTriggersImmediateUpsert(): void
    {
        $account = Account::create([
            'name' => 'Immediate',
        ]);

        $account->balance = '75.0000';

        $this->assertDatabaseHas('account_balances', [
            'account_id' => $account->id,
            'amount' => '75.0000',
            'source' => 'tester@example.com',
        ]);

        $refreshed = $account->fresh();
        $this->assertNotNull($refreshed);
        $this->assertSame('75.0000', $refreshed->balance);
    }

    public function testLegacyPendingAttributeKeyIsStillProcessedOnSave(): void
    {
        $account = Account::create([
            'name' => 'Legacy',
        ]);

        $pendingKey = sprintf(
            '_pending_temporal_%s_amount',
            AccountBalance::class
        );

        $account->setAttribute($pendingKey, '90.0000');

        $dispatcher = Account::getEventDispatcher();
        $dispatcher?->dispatch('eloquent.saved: ' . Account::class, $account);
        $dispatcher?->dispatch('eloquent.saved: *', $account);

        $this->assertDatabaseHas('account_balances', [
            'account_id' => $account->id,
            'amount' => '90.0000',
        ]);
        $this->assertArrayNotHasKey($pendingKey, $account->getAttributes());
    }
}
