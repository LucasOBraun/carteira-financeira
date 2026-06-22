<?php

namespace App\Repositories;

use App\Contracts\WalletRepositoryInterface;
use App\Models\FinancialOperation;
use App\Models\Wallet;

class WalletRepository implements WalletRepositoryInterface
{
    public function findByUserIdForUpdate(int $userId): Wallet
    {
        return Wallet::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function findByUserId(int $userId): Wallet
    {
        return Wallet::query()
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    public function findOperationByIdempotencyKey(string $key): ?FinancialOperation
    {
        return FinancialOperation::query()
            ->where('idempotency_key', $key)
            ->first();
    }

    public function applyBalanceChange(Wallet $wallet, string $delta): void
    {
        $newBalance = bcadd((string) $wallet->balance, $delta, 2);

        $wallet->update(['balance' => $newBalance]);
    }
}
