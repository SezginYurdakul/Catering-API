<?php

use App\Plugins\Di\Factory;
use App\Middleware\CorsMiddleware;

$di = Factory::getDi();
$router = $di->getShared('router');

// Set the base path for your project
$router->setBasePath('/catering_api');

// Apply CORS middleware to all routes
$router->before('GET|POST|PUT|PATCH|DELETE|OPTIONS', '/.*', function () {
    $corsMiddleware = new CorsMiddleware();
    $corsMiddleware->handle();
});

require_once '../routes/routes.php';

$router->set404(function () {
    throw new \App\Plugins\Http\Exceptions\NotFound(['error' => 'Route not defined']);
});

return $router;
