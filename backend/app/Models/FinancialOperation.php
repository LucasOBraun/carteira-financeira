<?php

namespace App\Models;

use App\Enums\OperationStatus;
use App\Enums\OperationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialOperation extends Model
{
    protected $fillable = [
        'type',
        'status',
        'idempotency_key',
        'initiated_by_user_id',
        'reverses_id',
        'reversed_by_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => OperationType::class,
            'status' => OperationStatus::class,
            'metadata' => 'array',
        ];
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_by_id');
    }

    public function isReversible(): bool
    {
        return $this->status === OperationStatus::Completed
            && $this->type !== OperationType::Reversal;
    }

    public function canBeReversedBy(int $userId): bool
    {
        if (! $this->isReversible()) {
            return false;
        }

        return match ($this->type) {
            OperationType::Deposit => $this->initiated_by_user_id === $userId,
            OperationType::Transfer => ($this->metadata['recipient_user_id'] ?? null) === $userId,
            default => false,
        };
    }

    public function reverseActionLabel(): ?string
    {
        if (! $this->isReversible()) {
            return null;
        }

        return match ($this->type) {
            OperationType::Deposit => 'Estornar',
            OperationType::Transfer => 'Devolver',
            default => null,
        };
    }

    public function participantName(): ?string
    {
        return match ($this->type) {
            OperationType::Transfer => $this->resolveMetadataName('sender_name', 'sender_user_id'),
            OperationType::Reversal => $this->initiatedBy?->name
                ?? $this->resolveMetadataName('initiated_by_name', 'initiated_by_user_id'),
            default => null,
        };
    }

    public function statementDescription(?int $viewerUserId): ?string
    {
        return match ($this->type) {
            OperationType::Transfer => $this->describeTransfer($viewerUserId),
            OperationType::Reversal => $this->describeReversal(),
            default => null,
        };
    }

    private function describeTransfer(?int $viewerUserId): string
    {
        $senderName = $this->resolveMetadataName('sender_name', 'sender_user_id');
        $recipientName = $this->resolveMetadataName('recipient_name', 'recipient_user_id');
        $senderId = $this->metadata['sender_user_id'] ?? null;
        $recipientId = $this->metadata['recipient_user_id'] ?? null;

        if ($viewerUserId === $senderId) {
            return "Transferiu para {$recipientName}";
        }

        if ($viewerUserId === $recipientId) {
            return "Transferido por {$senderName}";
        }

        return "De {$senderName} para {$recipientName}";
    }

    private function describeReversal(): string
    {
        $initiatedByName = $this->initiatedBy?->name
            ?? $this->resolveMetadataName('initiated_by_name', 'initiated_by_user_id');

        $originalType = $this->metadata['reversed_operation_type']
            ?? $this->reverses?->type?->value;

        return match ($originalType) {
            OperationType::Transfer->value => "Devolvido por {$initiatedByName}",
            OperationType::Deposit->value => "Estornado por {$initiatedByName}",
            default => "Realizado por {$initiatedByName}",
        };
    }

    private function resolveMetadataName(string $nameKey, string $idKey): string
    {
        if (! empty($this->metadata[$nameKey])) {
            return $this->metadata[$nameKey];
        }

        $userId = $this->metadata[$idKey] ?? null;

        if ($userId) {
            return User::query()->whereKey($userId)->value('name') ?? 'Usuário';
        }

        return 'Usuário';
    }
}
