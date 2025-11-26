<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

class DuplicateResourceException extends DomainException
{
    protected string $errorCode = 'DUPLICATE_RESOURCE';
    
    public function __construct(string $resourceType, string $field, mixed $value)
    {
        parent::__construct(
            "{$resourceType} with {$field} '{$value}' already exists",
            [
                'resource_type' => $resourceType, 
                'field' => $field, 
                'value' => $value
            ]
        );
    }
}