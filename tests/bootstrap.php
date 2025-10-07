<?php

declare(strict_types=1);

// Test bootstrap file
require_once __DIR__ . '/../vendor/autoload.php';

// Set up environment for testing
$_ENV['APP_ENV'] = 'testing';

// Create a minimal .env for testing if it doesn't exist
$testEnvFile = __DIR__ . '/../.env.testing';
if (!file_exists($testEnvFile)) {
    file_put_contents($testEnvFile, "
APP_ENV=testing
DB_HOST=localhost
DB_DATABASE=catering_test_db
DB_USERNAME=test_user
DB_PASSWORD=test_password
JWT_SECRET_KEY=test_secret_key_for_jwt_tokens_in_testing
LOGIN_USERNAME=test_admin
LOGIN_PASSWORD=test_password_hash
");
}

// Load test environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env.testing');
$dotenv->safeLoad();

// Disable error output for clean test results
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Start session for middleware tests
if (!session_id()) {
    session_start();
}