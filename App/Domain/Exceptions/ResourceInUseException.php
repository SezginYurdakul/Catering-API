<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

class ResourceInUseException extends DomainException
{
    protected string $errorCode = 'RESOURCE_IN_USE';
    
    public function __construct(string $resourceType, mixed $identifier, string $usedBy)
    {
        parent::__construct(
            "This {$resourceType} cannot be deleted because it is currently in use by related {$usedBy}.",
            [
                'resource_type' => $resourceType,
                'resource_id' => $identifier,
                'used_by' => $usedBy
            ]
        );
    }
}