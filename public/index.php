<?php

declare(strict_types=1);

// Start output buffering to catch any unwanted output
ob_start();

// Set up error handling for API responses (no HTML output)
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');

// Autoloader
require_once BASE_PATH . '/vendor/autoload.php';

// Helper function for clean JSON responses
function cleanOutputBuffer(): void
{
    if (ob_get_level()) {
        ob_clean();
    }
}

// Load environment and configuration
if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
}

// Load services and configuration
require_once CONFIG_PATH . '/config.php';
require_once CONFIG_PATH . '/services.php';

// Register global error handler
\App\Helpers\ErrorHandler::register();

// Initialize router
$router = require_once BASE_PATH . '/routes/router.php';

// Run application
try {
    $router->run();
} catch (\App\Plugins\Http\ApiException $e) {
    cleanOutputBuffer();
    $e->send();
} catch (\Exception $e) {
    cleanOutputBuffer();
    \App\Helpers\ErrorHandler::handle($e);
} finally {
    // Ensure output buffer is handled
    if (ob_get_level()) {
        ob_end_flush();
    }
}
