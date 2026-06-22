<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_transfer_to_another_user(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'recipient@test.com']);
        Wallet::factory()->for($sender)->withBalance('100.00')->create();
        Wallet::factory()->for($recipient)->withBalance('0.00')->create();

        $response = $this->actingAs($sender)->postJson('/api/wallet/transfer', [
            'recipient_email' => 'recipient@test.com',
            'amount' => '40.00',
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('wallet.balance', '60.00');

        $this->assertDatabaseHas('wallets', ['user_id' => $recipient->id, 'balance' => '40.00']);
    }

    public function test_transfer_fails_with_insufficient_balance(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'recipient@test.com']);
        Wallet::factory()->for($sender)->withBalance('10.00')->create();
        Wallet::factory()->for($recipient)->withBalance('0.00')->create();

        $response = $this->actingAs($sender)->postJson('/api/wallet/transfer', [
            'recipient_email' => 'recipient@test.com',
            'amount' => '50.00',
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error_code', 'INSUFFICIENT_BALANCE');
    }
}
