<?php

declare(strict_types=1);

namespace App\Controllers;

use Firebase\JWT\JWT;
use App\Controllers\BaseController;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Unauthorized;
use App\Helpers\Logger;

class AuthController extends BaseController
{
    private string $secretKey;
    private string $username;
    private string $password;
    protected Logger $logger;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $this->secretKey = $config['jwt']['secret_key'];
        $this->username = $config['auth']['username'];
        $this->password = $config['auth']['password'];
        $this->logger = new Logger(__DIR__ . '/../../logs/api.log');

        if (class_exists('App\\Plugins\\Di\\Factory')) {
            try {
                $this->logger = \App\Plugins\Di\Factory::getDi()->getShared('logger');
            } catch (\Throwable) {
                //If logger service is not available, use the default logger
            }
        }
    }

    /**
     * Login method to authenticate the user and generate a JWT token.
     * @return void
     */
    public function login(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $now = date('c');

            // Validate JSON input
            if ($data === null || !isset($data['username']) || !isset($data['password'])) {
                if ($this->logger) {
                    $this->logger->warning('Login attempt with invalid JSON or missing credentials', [
                        'ip' => $ip,
                        'user_agent' => $userAgent,
                        'time' => $now
                    ]);
                }
                $errorResponse = new Unauthorized(["error" => "Invalid JSON or missing username/password"]);
                $errorResponse->send();
                return;
            }

        // Check if username and password match
        if ($data['username'] === $this->username && password_verify($data['password'], $this->password)) {
            $payload = [
                'iss' => 'http://localhost',
                'iat' => time(),
                'exp' => time() + 360000, // Token expires in 1 year 
                'user' => $this->username,
            ];

            // Generate JWT token
            $jwt = JWT::encode($payload, $this->secretKey, 'HS256');

                if ($this->logger) {
                    $this->logger->info('Successful login', [
                        'ip' => $ip,
                        'user_agent' => $userAgent,
                        'time' => $now
                    ]);
                }

                $response = new Ok(['token' => $jwt]);
                $response->send();
        } else {
                if ($this->logger) {
                    $this->logger->warning('Failed login attempt', [
                        'ip' => $ip,
                        'user_agent' => $userAgent,
                        'time' => $now
                    ]);
                }
                $response = new Unauthorized(['error' => 'Invalid credentials']);
                $response->send();
        }
    }
}
