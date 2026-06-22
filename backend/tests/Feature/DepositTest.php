<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_deposit(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->for($user)->withBalance('0.00')->create();

        $response = $this->actingAs($user)->postJson('/api/wallet/deposit', [
            'amount' => '150.50',
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('wallet.balance', '150.50');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => '150.50',
        ]);
    }
}
