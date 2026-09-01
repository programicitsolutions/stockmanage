<?php

namespace App\Exceptions;

use RuntimeException;

class ImmutableStockTransactionException extends RuntimeException
{
    public static function cannotModify(): self
    {
        return new self('Stock transactions are a permanent ledger and cannot be modified or deleted.');
    }
}
