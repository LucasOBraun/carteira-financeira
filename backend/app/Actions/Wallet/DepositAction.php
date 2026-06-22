<?php

namespace App\Actions\Wallet;

use App\Contracts\WalletRepositoryInterface;
use App\DTOs\DepositDto;
use App\Enums\LedgerDirection;
use App\Enums\OperationStatus;
use App\Enums\OperationType;
use App\Models\FinancialOperation;
use App\Models\LedgerEntry;

class DepositAction
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
    ) {}

    public function execute(DepositDto $dto): FinancialOperation
    {
        $existing = $this->walletRepository->findOperationByIdempotencyKey($dto->idempotencyKey);
        if ($existing) {
            return $existing->load('ledgerEntries.wallet.user');
        }

        $wallet = $this->walletRepository->findByUserIdForUpdate($dto->userId);

        $operation = FinancialOperation::query()->create([
            'type' => OperationType::Deposit,
            'status' => OperationStatus::Completed,
            'idempotency_key' => $dto->idempotencyKey,
            'initiated_by_user_id' => $dto->userId,
            'metadata' => ['amount' => $dto->amount],
        ]);

        LedgerEntry::query()->create([
            'financial_operation_id' => $operation->id,
            'wallet_id' => $wallet->id,
            'direction' => LedgerDirection::Credit,
            'amount' => $dto->amount,
        ]);

        $this->walletRepository->applyBalanceChange($wallet, $dto->amount);

        return $operation->load('ledgerEntries.wallet.user');
    }
}
