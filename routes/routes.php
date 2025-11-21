<?php

declare(strict_types=1);

namespace App\Routes;

use App\Controllers\FacilityController;
use App\Controllers\IndexController;
use App\Controllers\LocationController;
use App\Controllers\TagController;
use App\Plugins\Di\Factory;
use Bramus\Router\Router;

/** @var Router $router */
$router = Factory::getDi()->getShared('router');

// Facility routes
// GET /facilities supports multiple modes:
// 1. List all: /facilities?page=1&per_page=10
// 2. Field-specific search: /facilities?city=Amsterdam&tag=Wedding&operator=AND
// 3. Legacy search: /facilities?query=Amsterdam&filter=city,facility_name
$router->get('/facilities',          FacilityController::class . '@getFacilities');
$router->get('/facilities/(\d+)',    FacilityController::class . '@getFacilityById');
$router->post('/facilities',         FacilityController::class . '@createFacility');
$router->patch('/facilities/(\d+)',  FacilityController::class . '@updateFacility');
$router->put('/facilities/(\d+)',    FacilityController::class . '@updateFacility');
$router->delete('/facilities/(\d+)', FacilityController::class . '@deleteFacility');

// Location routes
$router->get('/locations',           LocationController::class . '@getAllLocations');
$router->get('/locations/(\d+)',     LocationController::class . '@getLocationById');
$router->post('/locations',          LocationController::class . '@createLocation');
$router->patch('/locations/(\d+)',   LocationController::class . '@updateLocation');
$router->put('/locations/(\d+)',     LocationController::class . '@updateLocation');
$router->delete('/locations/(\d+)',  LocationController::class . '@deleteLocation');

// Tag routes
$router->get('/tags',                TagController::class . '@getAllTags');
$router->get('/tags/(\d+)',          TagController::class . '@getTagById');
$router->post('/tags',               TagController::class . '@createTag');
$router->patch('/tags/(\d+)',        TagController::class . '@updateTag');
$router->put('/tags/(\d+)',          TagController::class . '@updateTag');
$router->delete('/tags/(\d+)',       TagController::class . '@deleteTag');

// Auth routes
$router->post('/auth/login', 'App\Controllers\AuthController@login');

// Health check route
$router->get('/health', IndexController::class . '@test');

// Root route
$router->get('/', IndexController::class . '@index');