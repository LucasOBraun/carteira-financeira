<?php

namespace App\Services\Wallet;

use App\Actions\Wallet\DepositAction;
use App\Actions\Wallet\ReverseOperationAction;
use App\Actions\Wallet\TransferAction;
use App\DTOs\DepositDto;
use App\DTOs\TransferDto;
use App\Models\FinancialOperation;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(
        private readonly DepositAction $depositAction,
        private readonly TransferAction $transferAction,
        private readonly ReverseOperationAction $reverseOperationAction,
    ) {}

    public function deposit(DepositDto $dto): FinancialOperation
    {
        return DB::transaction(fn () => $this->depositAction->execute($dto));
    }

    public function transfer(TransferDto $dto): FinancialOperation
    {
        return DB::transaction(fn () => $this->transferAction->execute($dto));
    }

    public function reverse(int $operationId, int $requestedByUserId): FinancialOperation
    {
        return DB::transaction(fn () => $this->reverseOperationAction->execute($operationId, $requestedByUserId));
    }
}
