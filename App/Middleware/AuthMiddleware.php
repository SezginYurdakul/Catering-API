<?php

declare(strict_types=1);

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Plugins\Http\Exceptions\Unauthorized;

class AuthMiddleware
{
    private string $secretKey;

    public function __construct()
    {
        // Load the secret key from the configuration
        $config = require __DIR__ . '/../../config/config.php';
        $this->secretKey = $config['jwt']['secret_key'];
    }

    /**
     * Handle method to check the Authorization header and validate the JWT token.
     * If the token is valid, the user information is stored in the session.
     * If the token is invalid or expired, a 401 Unauthorized response is sent.
     * 
     * @return void
     */
    public function handle(): void
    {
        // Check if getallheaders function exists (not available in CLI)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            // Fallback for CLI/testing environment
            $headers = [];
            foreach ($_SERVER as $key => $value) {
                if (str_starts_with($key, 'HTTP_')) {
                    $header = str_replace('_', '-', substr($key, 5));
                    $headers[$header] = $value;
                }
            }
        }
        
        $authHeader = $headers['Authorization'] ?? $headers['AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $jwt = $matches[1];

            try {
                // Decode the JWT token
                $decoded = JWT::decode($jwt, new Key($this->secretKey, 'HS256'));
                // Token is valid, proceed with the request
                $_SESSION['user'] = $decoded->user; // Store user info in session or global variable
            } catch (\Exception $e) {
                // Token is invalid or expired
                throw new Unauthorized('Invalid or expired token');
            }
        } else {
            // Authorization header is missing
            throw new Unauthorized('Authorization header not found');
        }
    }
}
