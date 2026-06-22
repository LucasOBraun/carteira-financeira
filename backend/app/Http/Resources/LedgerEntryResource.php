<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LedgerEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'direction' => $this->direction->value,
            'amount' => number_format((float) $this->amount, 2, '.', ''),
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
            'user' => $this->when(
                $this->relationLoaded('wallet') && $this->wallet?->relationLoaded('user'),
                fn () => new UserResource($this->wallet->user),
            ),
        ];
    }
}
