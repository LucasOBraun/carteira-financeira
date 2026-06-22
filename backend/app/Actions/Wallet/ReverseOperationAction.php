<?php

namespace App\Actions\Wallet;

use App\Contracts\WalletRepositoryInterface;
use App\Enums\LedgerDirection;
use App\Enums\OperationStatus;
use App\Enums\OperationType;
use App\Exceptions\OperationAlreadyReversedException;
use App\Exceptions\OperationNotReversibleException;
use App\Exceptions\UnauthorizedOperationException;
use App\Models\FinancialOperation;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Support\Str;

class ReverseOperationAction
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
    ) {}

    public function execute(int $operationId, int $requestedByUserId): FinancialOperation
    {
        $original = FinancialOperation::query()
            ->with('ledgerEntries.wallet')
            ->lockForUpdate()
            ->findOrFail($operationId);

        if ($original->status === OperationStatus::Reversed) {
            throw new OperationAlreadyReversedException();
        }

        if (! $original->isReversible()) {
            throw new OperationNotReversibleException();
        }

        if (! $original->canBeReversedBy($requestedByUserId)) {
            $message = match ($original->type) {
                OperationType::Transfer => 'Apenas o destinatário pode devolver uma transferência recebida.',
                OperationType::Deposit => 'Apenas o titular pode estornar o próprio depósito.',
                default => 'Você não tem permissão para realizar esta operação.',
            };

            throw new UnauthorizedOperationException($message);
        }

        $idempotencyKey = 'reversal-'.$original->id.'-'.Str::uuid()->toString();
        $initiatedBy = User::query()->findOrFail($requestedByUserId);

        $reversal = FinancialOperation::query()->create([
            'type' => OperationType::Reversal,
            'status' => OperationStatus::Completed,
            'idempotency_key' => $idempotencyKey,
            'initiated_by_user_id' => $requestedByUserId,
            'reverses_id' => $original->id,
            'metadata' => [
                'reversed_operation_id' => $original->id,
                'reversed_operation_type' => $original->type->value,
                'initiated_by_user_id' => $requestedByUserId,
                'initiated_by_name' => $initiatedBy->name,
                'original_sender_name' => $original->metadata['sender_name'] ?? null,
                'original_recipient_name' => $original->metadata['recipient_name'] ?? null,
            ],
        ]);

        foreach ($original->ledgerEntries as $entry) {
            $wallet = $this->walletRepository->findByUserIdForUpdate($entry->wallet->user_id);
            $inverseDirection = $entry->direction === LedgerDirection::Credit
                ? LedgerDirection::Debit
                : LedgerDirection::Credit;

            LedgerEntry::query()->create([
                'financial_operation_id' => $reversal->id,
                'wallet_id' => $wallet->id,
                'direction' => $inverseDirection,
                'amount' => $entry->amount,
            ]);

            $delta = $inverseDirection === LedgerDirection::Credit
                ? $entry->amount
                : bcmul($entry->amount, '-1', 2);

            $this->walletRepository->applyBalanceChange($wallet, $delta);
        }

        $original->update([
            'status' => OperationStatus::Reversed,
            'reversed_by_id' => $reversal->id,
        ]);

        return $reversal->load('ledgerEntries.wallet.user', 'reverses');
    }
}
