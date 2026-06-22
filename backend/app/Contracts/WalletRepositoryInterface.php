<?php

namespace App\Contracts;

use App\Models\FinancialOperation;
use App\Models\Wallet;

interface WalletRepositoryInterface
{
    public function findByUserIdForUpdate(int $userId): Wallet;

    public function findByUserId(int $userId): Wallet;

    public function findOperationByIdempotencyKey(string $key): ?FinancialOperation;

    public function applyBalanceChange(Wallet $wallet, string $delta): void;
}
