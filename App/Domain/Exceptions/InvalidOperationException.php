<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

/**
 * Thrown when a business rule is violated
 * Examples: 
 * - Cannot delete employee with active assignments
 */
class InvalidOperationException extends DomainException
{
    protected string $errorCode = 'INVALID_OPERATION';
    
    public function __construct(string $operation, string $reason, array $context = [])
    {
        parent::__construct(
            "Cannot {$operation}: {$reason}",
            array_merge(['operation' => $operation, 'reason' => $reason], $context)
        );
    }
}