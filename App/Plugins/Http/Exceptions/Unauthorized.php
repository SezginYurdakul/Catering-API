<?php

declare(strict_types=1);

namespace App\Plugins\Http\Exceptions;

use App\Plugins\Http;

class Unauthorized extends Http\ApiException {
    /**
     * Enhanced constructor for auth errors
     * @param mixed $body
     */
    public function __construct($body = 'Authentication required') {
        if (is_string($body)) {
            $body = [
                'error' => $body,
                'error_type' => 'auth_error'
            ];
        }
        
        parent::__construct(new Http\Response\Unauthorized($body));
    }
}
