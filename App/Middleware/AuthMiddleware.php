<?php

declare(strict_types=1);

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Plugins\Http\Exceptions\Unauthorized;
use App\Plugins\Di\Factory;
use App\Helpers\Logger;

class AuthMiddleware
{
    private string $secretKey;
    private ?Logger $logger;

    public function __construct()
    {
        // Load the secret key from the configuration
        $config = require __DIR__ . '/../../config/config.php';
        $this->secretKey = $config['jwt']['secret_key'];
        
        // Get logger from DI (optional, may not be available in tests)
        try {
            $this->logger = Factory::getDi()->getShared('logger');
        } catch (\Exception $e) {
            $this->logger = null;
        }
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
                // Log authentication failure
                if ($this->logger) {
                    $this->logger->log('WARNING', 'JWT validation failed', [
                        'error' => $e->getMessage(),
                        'token_preview' => substr($jwt, 0, 20) . '...',
                        'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                    ]);
                }
                // Token is invalid or expired
                throw new Unauthorized('Invalid or expired token');
            }
        } else {
            // Log missing authorization header
            if ($this->logger) {
                $this->logger->log('WARNING', 'Missing or invalid Authorization header', [
                    'header_present' => !empty($authHeader),
                    'header_format' => $authHeader ? 'invalid' : 'missing',
                    'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                ]);
            }
            // Authorization header is missing
            throw new Unauthorized('Authorization header not found');
        }
    }
}
