<?php

declare(strict_types=1);

namespace App\Plugins\Http\Exceptions;

use App\Plugins\Http;

class NotFound extends Http\ApiException {
    /**
     * Constructor with smart resource messages
     * @param mixed $body Can be string message or array, or resource+identifier for smart messages
     */
    public function __construct($body = '', string $resource = '', string $identifier = '') {
        // Smart resource-based message generation
        if ($resource && $identifier) {
            $body = [
                'error' => "$resource with ID '$identifier' not found",
                'error_type' => 'not_found'
            ];
        } elseif ($resource) {
            $body = [
                'error' => "$resource not found", 
                'error_type' => 'not_found'
            ];
        } elseif (is_string($body) && $body) {
            $body = [
                'error' => $body,
                'error_type' => 'not_found'
            ];
        } elseif (!$body) {
            $body = [
                'error' => 'Resource not found',
                'error_type' => 'not_found'
            ];
        }
        
        parent::__construct(new Http\Response\NotFound($body));
    }
}
