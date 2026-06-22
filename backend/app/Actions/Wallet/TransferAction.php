<?php

namespace App\Actions\Wallet;

use App\Contracts\WalletRepositoryInterface;
use App\DTOs\TransferDto;
use App\Enums\LedgerDirection;
use App\Enums\OperationStatus;
use App\Enums\OperationType;
use App\Exceptions\InsufficientBalanceException;
use App\Models\FinancialOperation;
use App\Models\LedgerEntry;
use App\Models\User;

class TransferAction
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
    ) {}

    public function execute(TransferDto $dto): FinancialOperation
    {
        $existing = $this->walletRepository->findOperationByIdempotencyKey($dto->idempotencyKey);
        if ($existing) {
            return $existing->load('ledgerEntries.wallet.user');
        }

        $recipient = User::query()->where('email', $dto->recipientEmail)->firstOrFail();
        $sender = User::query()->findOrFail($dto->senderUserId);

        if ($recipient->id === $dto->senderUserId) {
            throw new \InvalidArgumentException('Não é possível transferir para si mesmo.');
        }

        $senderWallet = $this->walletRepository->findByUserIdForUpdate($dto->senderUserId);
        $recipientWallet = $this->walletRepository->findByUserIdForUpdate($recipient->id);

        if (bccomp($senderWallet->balance, $dto->amount, 2) < 0) {
            throw new InsufficientBalanceException();
        }

        $operation = FinancialOperation::query()->create([
            'type' => OperationType::Transfer,
            'status' => OperationStatus::Completed,
            'idempotency_key' => $dto->idempotencyKey,
            'initiated_by_user_id' => $dto->senderUserId,
            'metadata' => [
                'amount' => $dto->amount,
                'recipient_email' => $dto->recipientEmail,
                'sender_user_id' => $dto->senderUserId,
                'recipient_user_id' => $recipient->id,
                'sender_name' => $sender->name,
                'recipient_name' => $recipient->name,
            ],
        ]);

        LedgerEntry::query()->create([
            'financial_operation_id' => $operation->id,
            'wallet_id' => $senderWallet->id,
            'direction' => LedgerDirection::Debit,
            'amount' => $dto->amount,
        ]);

        LedgerEntry::query()->create([
            'financial_operation_id' => $operation->id,
            'wallet_id' => $recipientWallet->id,
            'direction' => LedgerDirection::Credit,
            'amount' => $dto->amount,
        ]);

        $this->walletRepository->applyBalanceChange($senderWallet, bcmul($dto->amount, '-1', 2));
        $this->walletRepository->applyBalanceChange($recipientWallet, $dto->amount);

        return $operation->load('ledgerEntries.wallet.user');
    }
}
