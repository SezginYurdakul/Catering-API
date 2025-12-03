<?php

declare(strict_types=1);

namespace App\Middleware;

class CorsMiddleware
{
    /**
     * Handle CORS headers and preflight requests.
     *
     * @return void
     */
    public function handle(): void
    {
        // Set CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
        header('Access-Control-Expose-Headers: Content-Disposition, X-Total-Count');
        header('Access-Control-Max-Age: 86400');

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit();
        }
    }
}
