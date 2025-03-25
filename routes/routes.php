<?php

declare(strict_types=1);

namespace App\Routes;

use App\Controllers\FacilityController;
use App\Controllers\LocationController;
use App\Controllers\TagController;
use App\Plugins\Di\Factory;
use Bramus\Router\Router;

/** @var Router $router */
$router = Factory::getDi()->getShared('router');

// Facility routes
$router->get('/facilities',          FacilityController::class . '@getAllFacilities');
$router->get('/facilities/(\d+)',    FacilityController::class . '@getFacilityById');
$router->post('/facilities',         FacilityController::class . '@createFacility');
$router->put('/facilities/(\d+)',    FacilityController::class . '@updateFacility');
$router->delete('/facilities/(\d+)', FacilityController::class . '@deleteFacility');

// Location routes
$router->get('/locations',           LocationController::class . '@getAllLocations');
$router->get('/locations/(\d+)',     LocationController::class . '@getLocationById');
$router->post('/locations',          LocationController::class . '@createLocation');
$router->put('/locations/(\d+)',     LocationController::class . '@updateLocation');
$router->delete('/locations/(\d+)',  LocationController::class . '@deleteLocation');

// Tag routes
$router->get('/tags',                TagController::class . '@getAllTags');
$router->get('/tags/(\d+)',          TagController::class . '@getTagById');
$router->post('/tags',               TagController::class . '@createTag');
$router->put('/tags/(\d+)',          TagController::class . '@updateTag');
$router->delete('/tags/(\d+)',       TagController::class . '@deleteTag');