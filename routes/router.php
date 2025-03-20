<?php

use App\Plugins\Di\Factory;

$di = Factory::getDi();
$router = $di->getShared('router');

// Set the base path for your project
$router->setBasePath('/web_backend_test_catering_api');

require_once '../routes/routes.php';

$router->set404(function () {
    throw new \App\Plugins\Http\Exceptions\NotFound(['error' => 'Route not defined']);
});

return $router;
