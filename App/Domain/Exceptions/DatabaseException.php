<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

/**
 * Thrown when database operations fail
 * Should be caught in service layer and logged with full details
 */
class DatabaseException extends DomainException
{
    protected string $errorCode = 'DATABASE_ERROR';
    
    public function __construct(string $operation, string $table, ?string $details = null, array $context = [])
    {
        $message = "Database operation failed: {$operation} on {$table}";
        if ($details) {
            $message .= " - {$details}";
        }
        
        parent::__construct(
            $message,
            array_merge([
                'operation' => $operation,
                'table' => $table,
                'details' => $details
            ], $context)
        );
    }
}