<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Autoloader
require_once '../vendor/autoload.php';

// Load Config
$config = require_once '../config/config.php';

// Services
require_once '../config/services.php';

// Router
$router = require_once '../routes/router.php';

// Run application through router:
try {
    $router->run();
} catch (\App\Plugins\Http\ApiException $e) {
    // Send the API exception to the client:
    $e->send();
} catch (Exception $e) {
    // For debugging purposes, throw the initial exception:
    throw $e;
}
