<?php

namespace App\DTOs;

readonly class DepositDto
{
    public function __construct(
        public int $userId,
        public string $amount,
        public string $idempotencyKey,
    ) {}
}
