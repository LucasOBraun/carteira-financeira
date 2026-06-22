<?php

namespace App\Http\Controllers\Api;

use App\DTOs\DepositDto;
use App\DTOs\TransferDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\TransferRequest;
use App\Http\Resources\FinancialOperationResource;
use App\Http\Resources\WalletResource;
use App\Enums\OperationType;
use App\Models\FinancialOperation;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function show(Request $request): WalletResource
    {
        $wallet = $request->user()->wallet()->firstOrFail();

        return new WalletResource($wallet);
    }

    public function transactions(Request $request): AnonymousResourceCollection
    {
        $walletId = $request->user()->wallet()->value('id');

        $operations = FinancialOperation::query()
            ->whereHas('ledgerEntries', fn ($query) => $query->where('wallet_id', $walletId))
            ->with(['ledgerEntries.wallet.user', 'initiatedBy', 'reverses'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return FinancialOperationResource::collection($operations);
    }

    public function deposit(DepositRequest $request): JsonResponse
    {
        $amount = number_format((float) $request->validated('amount'), 2, '.', '');

        $operation = $this->walletService->deposit(new DepositDto(
            userId: $request->user()->id,
            amount: $amount,
            idempotencyKey: $request->validated('idempotency_key'),
        ));

        return response()->json([
            'message' => 'Depósito realizado com sucesso.',
            'operation' => new FinancialOperationResource($operation),
            'wallet' => new WalletResource($request->user()->wallet()->first()->refresh()),
        ], 201);
    }

    public function transfer(TransferRequest $request): JsonResponse
    {
        $amount = number_format((float) $request->validated('amount'), 2, '.', '');

        $operation = $this->walletService->transfer(new TransferDto(
            senderUserId: $request->user()->id,
            recipientEmail: $request->validated('recipient_email'),
            amount: $amount,
            idempotencyKey: $request->validated('idempotency_key'),
        ));

        return response()->json([
            'message' => 'Transferência realizada com sucesso.',
            'operation' => new FinancialOperationResource($operation),
            'wallet' => new WalletResource($request->user()->wallet()->first()->refresh()),
        ], 201);
    }

    public function reverse(Request $request, int $id): JsonResponse
    {
        $original = FinancialOperation::query()->findOrFail($id);

        $reversal = $this->walletService->reverse($id, $request->user()->id);

        $message = match ($original->type) {
            OperationType::Transfer => 'Transferência devolvida com sucesso.',
            OperationType::Deposit => 'Depósito estornado com sucesso.',
            default => 'Operação revertida com sucesso.',
        };

        return response()->json([
            'message' => $message,
            'operation' => new FinancialOperationResource($reversal),
            'wallet' => new WalletResource($request->user()->wallet()->first()->refresh()),
        ]);
    }
}
