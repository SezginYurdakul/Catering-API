<?php

declare(strict_types=1);

namespace App\Plugins\Http\Exceptions;

use App\Plugins\Http;

class InternalServerError extends Http\ApiException {
    /**
     * Environment-aware constructor for server errors
     * @param mixed $body
     */
    public function __construct($body = 'Database operation failed') {
        $isDev = ($_ENV['APP_ENV'] ?? 'production') === 'development';
        
        if (is_string($body)) {
            $body = [
                'error' => $isDev ? $body : 'A server error occurred',
                'error_type' => 'server_error'
            ];
        }
        
        parent::__construct(new Http\Response\InternalServerError($body));
    }
}
