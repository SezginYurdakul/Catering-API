<?php

declare(strict_types=1);

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    private string $secretKey;

    public function __construct()
    {
        // Load the secret key from the configuration
        $config = require __DIR__ . '/../../config/config.php';
        $this->secretKey = $config['jwt']['secret_key'];
    }

    public function handle(): void
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $jwt = $matches[1];

            try {
                // Decode the JWT token
                $decoded = JWT::decode($jwt, new Key($this->secretKey, 'HS256'));
                // Token is valid, proceed with the request
                $_SESSION['user'] = $decoded->user; // Store user info in session or global variable
            } catch (\Exception $e) {
                // Token is invalid or expired
                http_response_code(401);
                echo json_encode(['message' => 'Invalid or expired token']);
                exit;
            }
        } else {
            // Authorization header is missing
            http_response_code(401);
            echo json_encode(['message' => 'Authorization header not found']);
            exit;
        }
    }
}