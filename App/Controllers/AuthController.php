<?php

declare(strict_types=1);

namespace App\Controllers;

use Firebase\JWT\JWT;
use App\Controllers\BaseController;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Unauthorized;

class AuthController extends BaseController
{
    private string $secretKey;
    private string $username;
    private string $password;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $this->secretKey = $config['jwt']['secret_key'];
        $this->username = $config['auth']['username'];
        $this->password = $config['auth']['password'];
    }

    /**
     * Login method to authenticate the user and generate a JWT token.
     * @return void
     */
    public function login(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate JSON input
        if ($data === null || !isset($data['username']) || !isset($data['password'])) {
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

            $response = new Ok(['token' => $jwt]);
            $response->send();
        } else {
            $response = new Unauthorized(['error' => 'Invalid credentials']);
            $response->send();
        }
    }
}
