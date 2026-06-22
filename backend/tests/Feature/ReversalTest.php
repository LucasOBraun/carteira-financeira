<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReversalTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipient_can_return_transfer(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'recipient@test.com']);
        Wallet::factory()->for($sender)->withBalance('100.00')->create();
        Wallet::factory()->for($recipient)->withBalance('0.00')->create();

        $transfer = $this->actingAs($sender)->postJson('/api/wallet/transfer', [
            'recipient_email' => 'recipient@test.com',
            'amount' => '25.00',
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        $operationId = $transfer->json('operation.id');

        $reverse = $this->actingAs($recipient)->postJson("/api/wallet/transactions/{$operationId}/reverse");

        $reverse->assertOk()
            ->assertJsonPath('message', 'Transferência devolvida com sucesso.');

        $this->assertDatabaseHas('wallets', ['user_id' => $sender->id, 'balance' => '100.00']);
        $this->assertDatabaseHas('wallets', ['user_id' => $recipient->id, 'balance' => '0.00']);
    }

    public function test_sender_cannot_return_transfer(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'recipient@test.com']);
        Wallet::factory()->for($sender)->withBalance('100.00')->create();
        Wallet::factory()->for($recipient)->withBalance('0.00')->create();

        $transfer = $this->actingAs($sender)->postJson('/api/wallet/transfer', [
            'recipient_email' => 'recipient@test.com',
            'amount' => '25.00',
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        $operationId = $transfer->json('operation.id');

        $this->actingAs($sender)
            ->postJson("/api/wallet/transactions/{$operationId}/reverse")
            ->assertForbidden()
            ->assertJsonPath('error_code', 'UNAUTHORIZED_OPERATION');
    }

    public function test_owner_can_reverse_own_deposit(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->for($user)->withBalance('0.00')->create();

        $deposit = $this->actingAs($user)->postJson('/api/wallet/deposit', [
            'amount' => '50.00',
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        $operationId = $deposit->json('operation.id');

        $this->actingAs($user)
            ->postJson("/api/wallet/transactions/{$operationId}/reverse")
            ->assertOk()
            ->assertJsonPath('message', 'Depósito estornado com sucesso.');

        $this->assertDatabaseHas('wallets', ['user_id' => $user->id, 'balance' => '0.00']);
    }
}
