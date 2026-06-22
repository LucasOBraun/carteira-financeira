<?php

namespace App\Exceptions;

use Exception;

class OperationAlreadyReversedException extends Exception
{
    public function __construct()
    {
        parent::__construct('Esta operação já foi estornada.');
    }
}
