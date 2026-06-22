<?php

namespace App\DTOs;

readonly class TransferDto
{
    public function __construct(
        public int $senderUserId,
        public string $recipientEmail,
        public string $amount,
        public string $idempotencyKey,
    ) {}
}
