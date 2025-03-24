<?php

/** @var Bramus\Router\Router $router */
$router = App\Plugins\Di\Factory::getDi()->getShared('router');

// Facility routes
$router->get('/facilities', App\Controllers\FacilityController::class . '@getAllFacilities');
$router->get('/facilities/(\d+)', App\Controllers\FacilityController::class . '@getFacilityById');
$router->post('/facilities', App\Controllers\FacilityController::class . '@createFacility');
$router->put('/facilities/(\d+)', App\Controllers\FacilityController::class . '@updateFacility');
$router->delete('/facilities/(\d+)', App\Controllers\FacilityController::class . '@deleteFacility');

// Location Routes
$router->get('/locations', App\Controllers\LocationController::class . '@getAllLocations');
$router->get('/locations/(\d+)', App\Controllers\LocationController::class . '@getLocationById');
$router->post('/locations', App\Controllers\LocationController::class . '@createLocation');
$router->put('/locations/(\d+)', App\Controllers\LocationController::class . '@updateLocation');
$router->delete('/locations/(\d+)', App\Controllers\LocationController::class . '@deleteLocation');

// Tag Routes
$router->get('/tags', App\Controllers\TagController::class . '@getAllTags');
$router->get('/tags/(\d+)', App\Controllers\TagController::class . '@getTagById');
$router->post('/tags', App\Controllers\TagController::class . '@createTag');
$router->put('/tags/(\d+)', App\Controllers\TagController::class . '@updateTag');
$router->delete('/tags/(\d+)', App\Controllers\TagController::class . '@deleteTag');