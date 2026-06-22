<?php

namespace App\Exceptions;

use Exception;

class OperationNotReversibleException extends Exception
{
    public function __construct()
    {
        parent::__construct('Esta operação não pode ser estornada.');
    }
}
