<?php

declare(strict_types=1);

namespace App\Plugins\Http\Exceptions;

use App\Plugins\Http;

class ValidationException extends Http\ApiException 
{
    private array $errors;

    public function __construct($errors)
    {
        $this->errors = is_array($errors) ? $errors : ['error' => $errors];
        $message = is_array($errors) ? 'Validation failed' : (string) $errors;
        
        $body = [
            'error' => $message,
            'error_type' => 'validation_error',
            'validation_errors' => $this->errors
        ];
        
        parent::__construct(new Http\Response\BadRequest($body));
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}