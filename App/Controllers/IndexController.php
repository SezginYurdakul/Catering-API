<?php

namespace App\Controllers;

use App\Plugins\Http\Response as Status;
use App\Plugins\Http\Exceptions;

class IndexController extends BaseController {
    /**
     * Controller function used to test whether the project was set up properly.
     * @return void
     */
    public function test() {
        // Respond with 200 (OK):
        (new Status\Ok(['message' => 'Catering API is working!']))->send();
    }

    /**
     * API documentation - list all available endpoints
     * @return void
     */
    public function index() {
        $endpoints = [
            'api_name' => 'Catering API',
            'version' => '1.0.0',
            'base_url' => '/catering_api',
            'authentication' => [
                'type' => 'JWT Bearer Token',
                'endpoint' => 'POST /auth/login',
                'credentials' => [
                    'username' => 'admin',
                    'password' => 'admin'
                ]
            ],
            'endpoints' => [
                'facilities' => [
                    'GET /facilities' => 'List all facilities (with pagination, search, filter)',
                    'GET /facilities/{id}' => 'Get a specific facility by ID',
                    'POST /facilities' => 'Create a new facility',
                    'PUT /facilities/{id}' => 'Update a facility',
                    'PATCH /facilities/{id}' => 'Partially update a facility',
                    'DELETE /facilities/{id}' => 'Delete a facility'
                ],
                'locations' => [
                    'GET /locations' => 'List all locations (with pagination)',
                    'GET /locations/{id}' => 'Get a specific location by ID',
                    'POST /locations' => 'Create a new location',
                    'PUT /locations/{id}' => 'Update a location',
                    'PATCH /locations/{id}' => 'Partially update a location',
                    'DELETE /locations/{id}' => 'Delete a location'
                ],
                'tags' => [
                    'GET /tags' => 'List all tags (with pagination)',
                    'GET /tags/{id}' => 'Get a specific tag by ID',
                    'POST /tags' => 'Create a new tag',
                    'PUT /tags/{id}' => 'Update a tag',
                    'PATCH /tags/{id}' => 'Partially update a tag',
                    'DELETE /tags/{id}' => 'Delete a tag'
                ],
                'auth' => [
                    'POST /auth/login' => 'Login and get JWT token'
                ],
                'health' => [
                    'GET /health' => 'API health check'
                ]
            ],
            'features' => [
                'Pagination' => 'page & per_page query parameters',
                'Search' => 'Query-based search on facilities',
                'Filter' => 'Field-specific filtering',
                'Authentication' => 'JWT-based authentication',
                'Docker' => 'Fully containerized with Docker Compose',
                'SSL' => 'HTTPS with Let\'s Encrypt'
            ]
        ];

        (new Status\Ok($endpoints))->send();
    }
}
