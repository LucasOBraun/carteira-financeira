<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialOperationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'metadata' => $this->metadata,
            'is_reversible' => $this->isReversible(),
            'can_reverse' => $request->user()
                ? $this->canBeReversedBy($request->user()->id)
                : false,
            'reverse_action_label' => $request->user() && $this->canBeReversedBy($request->user()->id)
                ? $this->reverseActionLabel()
                : null,
            'participant_name' => $this->participantName(),
            'description' => $this->statementDescription($request->user()?->id),
            'reverses_id' => $this->reverses_id,
            'reversed_by_id' => $this->reversed_by_id,
            'ledger_entries' => LedgerEntryResource::collection($this->whenLoaded('ledgerEntries')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
