<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use Exception;

abstract class DomainException extends Exception
{
    protected string $errorCode;
    protected array $context = [];
    
    public function __construct(
        string $message = '', 
        array $context = [], 
        int $code = 0, 
        ?Exception $previous = null
    ) {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }
    
    public function getErrorCode(): string
    {
        return $this->errorCode ?? 'DOMAIN_ERROR';
    }
    
    public function getContext(): array
    {
        return $this->context;
    }
}