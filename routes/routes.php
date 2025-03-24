<?php

/** @var Bramus\Router\Router $router */
$router = App\Plugins\Di\Factory::getDi()->getShared('router');

// Facility routes
$router->get('/facilities', App\Controllers\FacilityController::class . '@getAllFacilities');
$router->get('/facilities/(\d+)', App\Controllers\FacilityController::class . '@getFacilityById');
$router->post('/facilities', App\Controllers\FacilityController::class . '@createFacility');
$router->put('/facilities/(\d+)', App\Controllers\FacilityController::class . '@updateFacility');
$router->delete('/facilities/(\d+)', App\Controllers\FacilityController::class . '@deleteFacility');
