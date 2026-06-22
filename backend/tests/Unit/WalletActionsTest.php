<?php

namespace Tests\Unit;

use App\Actions\Wallet\DepositAction;
use App\Services\Wallet\WalletService;
use App\DTOs\DepositDto;
use App\DTOs\TransferDto;
use App\Enums\OperationStatus;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OperationAlreadyReversedException;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WalletActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_adds_to_negative_balance(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->for($user)->withBalance('-50.00')->create();

        $action = app(DepositAction::class);
        $operation = $action->execute(new DepositDto(
            userId: $user->id,
            amount: '100.00',
            idempotencyKey: Str::uuid()->toString(),
        ));

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => '50.00',
        ]);

        $this->assertSame('deposit', $operation->type->value);
    }

    public function test_transfer_rejects_insufficient_balance(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'recipient@test.com']);
        Wallet::factory()->for($sender)->withBalance('10.00')->create();
        Wallet::factory()->for($recipient)->withBalance('0.00')->create();

        $this->expectException(InsufficientBalanceException::class);

        app(WalletService::class)->transfer(new TransferDto(
            senderUserId: $sender->id,
            recipientEmail: 'recipient@test.com',
            amount: '50.00',
            idempotencyKey: Str::uuid()->toString(),
        ));
    }

    public function test_reverse_creates_inverse_entries_and_blocks_double_reversal(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'recipient@test.com']);
        Wallet::factory()->for($sender)->withBalance('100.00')->create();
        Wallet::factory()->for($recipient)->withBalance('0.00')->create();

        $service = app(WalletService::class);

        $transfer = $service->transfer(new TransferDto(
            senderUserId: $sender->id,
            recipientEmail: 'recipient@test.com',
            amount: '30.00',
            idempotencyKey: Str::uuid()->toString(),
        ));

        $service->reverse($transfer->id, $recipient->id);

        $this->assertDatabaseHas('wallets', ['user_id' => $sender->id, 'balance' => '100.00']);
        $this->assertDatabaseHas('wallets', ['user_id' => $recipient->id, 'balance' => '0.00']);
        $this->assertDatabaseHas('financial_operations', [
            'id' => $transfer->id,
            'status' => OperationStatus::Reversed->value,
        ]);

        $this->expectException(OperationAlreadyReversedException::class);
        $service->reverse($transfer->id, $recipient->id);
    }
}
