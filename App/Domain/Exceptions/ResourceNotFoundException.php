<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

class ResourceNotFoundException extends DomainException
{
    protected string $errorCode = 'RESOURCE_NOT_FOUND';
    
    public function __construct(string $resourceType, mixed $identifier)
    {
        parent::__construct(
            "{$resourceType} with identifier '{$identifier}' not found",
            [
                'resource_type' => $resourceType, 
                'identifier' => $identifier
            ]
        );
    }
}