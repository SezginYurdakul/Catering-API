<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

/**
 * Thrown when external service calls fail
 * Examples: Payment gateway, Email service, SMS provider
 */
class ExternalServiceException extends DomainException
{
    protected string $errorCode = 'EXTERNAL_SERVICE_ERROR';
    
    public function __construct(string $service, string $operation, ?string $reason = null, array $context = [])
    {
        $message = "External service '{$service}' failed during '{$operation}'";
        if ($reason) {
            $message .= ": {$reason}";
        }
        
        parent::__construct(
            $message,
            array_merge([
                'service' => $service,
                'operation' => $operation,
                'reason' => $reason
            ], $context)
        );
    }
}