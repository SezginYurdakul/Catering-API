<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

/**
 * Thrown when a specific business rule is violated
 * More specific than InvalidOperationException
 * Examples:
 * - Employee cannot have more than 5 facilities
 * - Facility must have at least one tag
 * - Location cannot be deleted if it has facilities
 */
class BusinessRuleViolationException extends DomainException
{
    protected string $errorCode = 'BUSINESS_RULE_VIOLATION';
    
    public function __construct(string $rule, string $reason, array $context = [])
    {
        parent::__construct(
            "Business rule '{$rule}' violated: {$reason}",
            array_merge(['rule' => $rule, 'reason' => $reason], $context)
        );
    }
}