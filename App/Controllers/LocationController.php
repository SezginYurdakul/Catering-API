<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ILocationService;
use App\Helpers\InputSanitizer;
use App\Models\Location;
use App\Plugins\Di\Factory;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Response\NotFound;
use App\Plugins\Http\Response\BadRequest;
use App\Plugins\Http\Response\InternalServerError;
use App\Middleware\AuthMiddleware;


class LocationController
{
    private ILocationService $locationService;

    /**
     * Constructor to initialize the LocationService from the DI container and the AuthMiddleware.
     */
    public function __construct()
    {
        $this->locationService = Factory::getDi()->getShared('locationService');
        $authMiddleware = new AuthMiddleware();
        $authMiddleware->handle();
    }

    /**
     * Get all locations.
     * Sends a 200 OK response with the list of locations.
     * Sends a 500 Internal Server Error response in case of an exception.
     * 
     * @return void
     */
    public function getAllLocations(): void
    {
        try {
            // Get and sanitize pagination parameters
            $page = isset($_GET['page']) ? InputSanitizer::sanitizeId($_GET['page']) : 1;
            $perPage = isset($_GET['per_page']) ? InputSanitizer::sanitizeId($_GET['per_page']) : 10;

            // Validate pagination parameters
            if ($page === null || $perPage === null || $page <= 0 || $perPage <= 0) {
                $errorResponse = new BadRequest([
                    "error" => "Invalid pagination parameters. 'page' and 'per_page' must be positive integers."
                ]);
                $errorResponse->send();
                return;
            }
            // Call the service method with pagination
            $locations = $this->locationService->getAllLocations($page, $perPage);

            // Check if the requested page exceeds the total number of pages
            $totalItems = $locations['pagination']['total_items'];
            $totalPages = (int) ceil($totalItems / $perPage);
            if ($totalPages > 0 && $page > $totalPages) {
                $errorResponse = new BadRequest([
                    "error" => "The requested page ($page) exceeds the total number of pages ($totalPages)."
                ]);
                $errorResponse->send();
                return;
            }
            // Send the response
            $response = new Ok($locations); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
            $errorResponse->send();
        }
    }

    /**
     * Get a specific location by its ID.
     * Sends a 200 OK response if the location is found.
     * Sends a 404 Not Found response if the location does not exist.
     * Sends a 500 Internal Server Error response in case of an exception.
     * 
     * @param int $id
     * @return void
     */
    public function getLocationById($id): void
    {
        try {
            // Sanitize and validate the ID
            $id = InputSanitizer::sanitizeId($id);
            if ($id === null) {
                $errorResponse = new BadRequest(["error" => "Invalid location ID. It must be a positive integer."]);
                $errorResponse->send();
                return;
            }

            // Fetch the location by ID
            $location = $this->locationService->getLocationById((int) $id);

            if (!$location) {
                $errorResponse = new NotFound(["error" => "Location with ID $id not found."]); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $response = new Ok(["location" => $location]); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
            $errorResponse->send();
        }
    }

    /**
     * Create a new location.
     * Sends a 201 Created response with the created location.
     * Sends a 400 Bad Request response if required fields are missing.
     * Sends a 500 Internal Server Error response in case of an exception.
     * 
     * @return void
     */
    public function createLocation(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $requiredFields = [
                'city' => 'sanitizeAddress',
                'address' => 'sanitizeAddress',
                'zip_code' => 'sanitizeAddress',
                'country_code' => 'sanitizeAddress',
                'phone_number' => 'sanitizePhone'
            ];

            $sanitizedData = [];

            foreach ($requiredFields as $field => $sanitizeMethod) {
                if (empty($data[$field])) {
                    $errorResponse = new BadRequest(["error" => "Field '$field' is required.You may type incorrectly."]); // 400 Bad Request
                    $errorResponse->send();
                    return;
                }

                $sanitizedValue = InputSanitizer::$sanitizeMethod($data[$field]);

                if ($sanitizedValue === null) {
                    $errorResponse = new BadRequest(["error" => "Invalid $field. Please provide a valid $field."]);
                    $errorResponse->send();
                    return;
                }

                $sanitizedData[$field] = $sanitizedValue;
            }

            // Create a new Location object
            $location = new Location(
                0,
                $sanitizedData['city'],
                $sanitizedData['address'],
                $sanitizedData['zip_code'],
                $sanitizedData['country_code'],
                $sanitizedData['phone_number']
            );

            $result = $this->locationService->createLocation($location);
            $response = new Created($result); // 201 Created response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
            $errorResponse->send();
        }
    }

    /**
     * Update an existing location by its ID.
     * Sends a 200 OK response with the updated location.
     * Sends a 400 Bad Request response if required fields are missing.
     * Sends a 404 Not Found response if the location does not exist.
     * Sends a 500 Internal Server Error response in case of an exception.
     * 
     * @param int $id
     * @return void
     */
    public function updateLocation($id): void
    {
        try {
            // Sanitize and validate the ID
            $id = InputSanitizer::sanitizeId($id);
            if ($id === null) {
                $errorResponse = new BadRequest(["error" => "Invalid location ID. It must be a positive integer."]);
                $errorResponse->send();
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Sanitize client data
            $fieldsToSanitize = [
                'city' => 'sanitizeAddress',
                'address' => 'sanitizeAddress',
                'zip_code' => 'sanitizeAddress',
                'country_code' => 'sanitizeAddress',
                'phone_number' => 'sanitizePhone'
            ];
            $sanitizedData = [];

            foreach ($fieldsToSanitize as $field => $sanitizeMethod) {
                if (isset($data[$field])) { // Check if the field is present in the request
                    $sanitizedValue = InputSanitizer::$sanitizeMethod($data[$field]);

                    if ($sanitizedValue === null) {
                        $errorResponse = new BadRequest(["error" => "Invalid $field. Please provide a valid $field."]);
                        $errorResponse->send();
                        return;
                    }

                    $sanitizedData[$field] = $sanitizedValue;
                }
            }

            // Check if at least one field is provided
            if (
                empty($sanitizedData['city']) && empty($sanitizedData['address']) && empty($sanitizedData['zip_code']) &&
                empty($sanitizedData['country_code']) && empty($sanitizedData['phone_number'])
            ) {
                $errorResponse = new BadRequest(["error" => "At least one field is required to update the location."]); // 400 Bad Request
                $errorResponse->send();
                return;
            }

            // Fetch the existing location to pass not updated fields
            $existingLocation = $this->locationService->getLocationById((int) $id);
            if (!$existingLocation) {
                $errorResponse = new NotFound(["error" => "Location with ID $id not found."]); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $location = new Location(
                (int) $id,
                $sanitizedData['city'] ?? $existingLocation->city,
                $sanitizedData['address'] ?? $existingLocation->address,
                $sanitizedData['zip_code'] ?? $existingLocation->zip_code,
                $sanitizedData['country_code'] ?? $existingLocation->country_code,
                $sanitizedData['phone_number'] ?? $existingLocation->phone_number
            );

            $result = $this->locationService->updateLocation($location);

            if (!$result) {
                $errorResponse = new NotFound(["error" => "Location with ID $id not found."]); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $response = new Ok($result); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
            $errorResponse->send();
        }
    }

    /**
     * Delete a location by its ID.
     * Sends a 204 No Content response if the location is successfully deleted.
     * Sends a 404 Not Found response if the location does not exist.
     * Sends a 500 Internal Server Error response in case of an exception.
     * 
     * @param int $id
     * @return void
     */
    public function deleteLocation($id): void
    {
        try {

            // Sanitize and validate the ID
            $id = InputSanitizer::sanitizeId($id);
            if ($id === null) {
                $errorResponse = new BadRequest(["error" => "Invalid location ID. It must be a positive integer."]);
                $errorResponse->send();
                return;
            }

            // Fetch the location to check if it exists
            $existingLocation = $this->locationService->getLocationById($id);
            $result = $this->locationService->deleteLocation($id);

            if (!$result) {
                $errorResponse = new NotFound(["eror" => "Location with ID $id not found."]); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $response = new NoContent(); // 204 No Content response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage() . "No change was made"]); // 500 Internal Server Error
            $errorResponse->send();
        }
    }
}
