<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedOperationException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? 'Você não tem permissão para realizar esta operação.');
    }
}
