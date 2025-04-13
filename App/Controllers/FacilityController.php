<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Facility;
use App\Plugins\Di\Factory;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Response\NotFound;
use App\Plugins\Http\Response\BadRequest;
use App\Plugins\Http\Response\InternalServerError;
use App\Services\IFacilityService;
use App\Middleware\AuthMiddleware;
use App\Helpers\InputSanitizer;

class FacilityController
{
    private IFacilityService $facilityService;

    /**
     * Constructor to initialize the FacilityService from the DI container and the AuthMiddleware.
     */
    public function __construct()
    {
        $this->facilityService = Factory::getDi()->getShared('facilityService');
        $authMiddleware = new AuthMiddleware();
        $authMiddleware->handle();
    }

    /**
     * Get facilities with optional pagination, search, and filtering.
     * Sends a 200 OK response with the list of facilities.
     * Sends a 400 Bad Request response if invalid parameters are provided.
     * Sends a 500 Internal Server Error response in case of an exception.
     * 
     * @return void
     */
    public function getFacilities(): void
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

            // Sanitize and validate query
            $query = isset($_GET['query']) ? InputSanitizer::sanitize(['value' => $_GET['query']])['value'] : null;

            // Sanitize and validate filters
            $filters = isset($_GET['filter']) ? explode(',', $_GET['filter']) : [];
            $validFilters = ['facility_name', 'city', 'tag'];

            foreach ($filters as $filter) {
                $filter = trim($filter); // Remove whitespace
                if (!in_array($filter, $validFilters)) {
                    $errorResponse = new BadRequest([
                        "error" => "Invalid filter provided: '$filter'. Valid filters are: " . implode(', ', $validFilters)
                    ]);
                    $errorResponse->send();
                    return;
                }
            }

            // Sanitize and validate operator
            $operator = isset($_GET['operator']) ? strtoupper(trim($_GET['operator'])) : 'OR';
            if (!in_array($operator, ['AND', 'OR'])) {
                $errorResponse = new BadRequest(["error" => "Invalid operator. Only 'AND' or 'OR' are allowed."]);
                $errorResponse->send();
                return;
            }

            // Call the service method with the provided parameters
            $facilitiesData = $this->facilityService->getFacilities($page, $perPage, $query, $filters, $operator);

            // Check if the requested page exceeds the total number of pages
            $totalItems = $facilitiesData['pagination']['total_items'];
            $totalPages = (int) ceil($totalItems / $perPage);
            if ($totalPages > 0 && $page > $totalPages) {
                $errorResponse = new BadRequest([
                    "error" => "The requested page ($page) exceeds the total number of pages ($totalPages)."
                ]);
                $errorResponse->send();
                return;
            }

            // Send the response
            $response = new Ok([
                'facilities' => $facilitiesData['facilities'],
                'pagination' => $facilitiesData['pagination']
            ]); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            // Handle exceptions and send a 500 Internal Server Error response
            $errorResponse = new InternalServerError([
                "error" => $e->getMessage()
            ]); // 500 Internal Server Error
            $errorResponse->send();
        }
    }

    /**
     * Get a specific facility by its ID.
     * Sends a 200 OK response if the facility is found.
     * Sends a 404 Not Found response if the facility does not exist.
     * Sends a 500 Internal Server Error response in case of an exception.
     * 
     * @param int $id
     * @return void
     */
    public function getFacilityById(int $id): void
    {
        try {
            $id = InputSanitizer::sanitizeId($id);
            if ($id === null) {
                $errorResponse = new BadRequest(["error" => "Invalid facility ID. It must be a positive integer."]);
                $errorResponse->send();
                return;
            }
            $facility = $this->facilityService->getFacilityById((int) $id);

            if (!$facility) {
                $errorResponse = new NotFound(["error" => "Facility with ID $id not found."]); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $response = new Ok([
                'facility' => $facility
            ]); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
            $errorResponse->send();
        }
    }

    /**
     * Create a new facility.
     * Sends a 201 Created response with the created facility.
     * Sends a 400 Bad Request response if required fields are missing.
     * Sends a 500 Internal Server Error response in case of an exception.
     * 
     * @return void
     */
    public function createFacility(): void
    {
        try {
            // Get and decode the request body
            $data = json_decode(file_get_contents('php://input'), true);

            // Validate required fields
            if (empty($data['name']) || empty($data['location_id'])) {
                $errorResponse = new BadRequest([
                    "error" => "The request body must include both 'name' and 'location_id' fields."
                ]);
                $errorResponse->send();
                return;
            }

            // Sanitize inputs
            $name = InputSanitizer::sanitize(['value' => $data['name']])['value'];
            $locationId = InputSanitizer::sanitizeId($data['location_id']);
            if ($locationId === null) {
                $errorResponse = new BadRequest(["error" => "Invalid location ID. It must be a positive integer."]);
                $errorResponse->send();
                return;
            }

            // Validate location
            $locationService = Factory::getDi()->getShared('locationService');
            $location = $locationService->getLocationById($locationId);
            if (!$location) {
                $errorResponse = new BadRequest(["error" => "Invalid Location ID. Location not found."]); // 400 Bad Request
                $errorResponse->send();
                return;
            }

            // Determine tags (tagNames or tagIds)
            $tagNames = [];
            $tagIds = [];

            if (!empty($data['tagNames']) && is_array($data['tagNames'])) {
                $tagNames = array_map('trim', $data['tagNames']); // Sanitize tag names
            }

            if (!empty($data['tagIds']) && is_array($data['tagIds'])) {
                foreach ($data['tagIds'] as $tagId) {
                    $sanitizedTagId = InputSanitizer::sanitizeId($tagId);
                    if ($sanitizedTagId !== null) {
                        $tagIds[] = $sanitizedTagId;
                    }
                }
            }

            // Create a new Facility object
            $facility = new Facility(
                0,
                $name,
                $location,
                date('Y-m-d H:i:s'),
                [] // Tags will be handled in the service
            );

            // Call the service method
            if (!empty($tagIds) && empty($tagNames)) {
                // Only tagIds are provided
                $result = $this->facilityService->createFacility($facility, $tagIds, []);
            } elseif (!empty($tagNames) && empty($tagIds)) {
                // Only tagNames are provided
                $result = $this->facilityService->createFacility($facility, [], $tagNames);
            } elseif (empty($tagIds) && empty($tagNames)) {
                // Neither tagIds nor tagNames are provided
                $result = $this->facilityService->createFacility($facility);
            } else {
                // Both tagIds and tagNames are provided, which is not allowed
                $errorResponse = new BadRequest([
                    "error" => "You cannot provide both 'tagIds' and 'tagNames' at the same time."
                ]);
                $errorResponse->send();
                return;
            }

            // Send the response
            $response = new Created($result); // 201 Created response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(['error' => $e->getMessage()]); // 500 Internal Server Error
            $errorResponse->send();
        }
    }

    /**
     * Update an existing facility by its ID.
     * Sends a 200 OK response with the updated facility.
     * Sends a 400 Bad Request response if required fields are missing.
     * Sends a 404 Not Found response if the facility does not exist.
     * Sends a 500 Internal Server Error response in case of an exception.
     * 
     * @param int $id
     * @return void
     */
    public function updateFacility(int $id): void
    {
        try {
            // Sanitize and validate the ID
            $id = InputSanitizer::sanitizeId($id);
            if ($id === null) {
                $errorResponse = new BadRequest(["error" => "Invalid facility ID. It must be a positive integer."]);
                $errorResponse->send();
                return;
            }
            // Decode the request body
            $data = json_decode(file_get_contents('php://input'), true);

            // Check if at least one valid field is provided
            if (empty($data['name']) && empty($data['location_id']) && empty($data['tagIds']) && empty($data['tagNames'])) {
                $errorResponse = new BadRequest([
                    "error" => "At least one valid field (facility name, location_id, tagIds, or tagNames) must be provided."
                ]);
                $errorResponse->send();
                return;
            }

            // Fetch the existing facility
            $existingFacility = $this->facilityService->getFacilityById($id);

            // Sanitize inputs
            $name = isset($data['name']) ? InputSanitizer::sanitize(['value' => $data['name']])['value'] : $existingFacility->name;
            $locationId = isset($data['location_id']) ? InputSanitizer::sanitizeId($data['location_id']) : $existingFacility->location->id;

            // Validate location if provided
            $location = $existingFacility->location;
            if ($locationId !== $existingFacility->location->id) {
                $locationService = Factory::getDi()->getShared('locationService');
                $location = $locationService->getLocationById($locationId);
                if (!$location) {
                    $errorResponse = new BadRequest(["error" => "Invalid Location ID. Location not found."]);
                    $errorResponse->send();
                    return;
                }
            }

            // Sanitize and validate tags
            $tagNames = [];
            $tagIds = [];

            if (!empty($data['tagNames']) && is_array($data['tagNames'])) {
                $tagNames = array_map('trim', $data['tagNames']); // Trim whitespace
                $tagNames = array_filter($tagNames, function ($tagName) {
                    return !empty($tagName); // Remove empty or whitespace-only names
                });
            }

            if (!empty($data['tagIds']) && is_array($data['tagIds'])) {
                foreach ($data['tagIds'] as $tagId) {
                    $sanitizedTagId = InputSanitizer::sanitizeId($tagId);
                    if ($sanitizedTagId !== null) {
                        $tagIds[] = $sanitizedTagId;
                    }
                }
            }

            // Create a Facility object
            $facility = new Facility(
                $id,
                $name,
                $location,
                $existingFacility->creation_date,
                [] // Tags will be handled in the service
            );

            // Call the service method
            if (!empty($tagNames)) {
                $result = $this->facilityService->updateFacility($facility, [], $tagNames);
            } elseif (!empty($tagIds)) {
                $result = $this->facilityService->updateFacility($facility, $tagIds, []);
            } else {
                $result = $this->facilityService->updateFacility($facility);
            }

            // Send the response
            $response = new Ok(['result' => $result]); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(['error' => $e->getMessage()]);
            $errorResponse->send();
        }
    }

    /**
     * Delete a facility by its ID.
     * Sends a 204 No Content response if the facility is successfully deleted.
     * Sends a 404 Not Found response if the facility does not exist.
     * Sends a 500 Internal Server Error response in case of an exception.
     * 
     * @param int $id
     * @return void
     */
    public function deleteFacility(int $id): void
    {
        try {
            $id = InputSanitizer::sanitizeId($id);
            if ($id === null) {
                $errorResponse = new BadRequest(["error" => "Invalid facility ID. It must be a positive integer."]);
                $errorResponse->send();
                return;
            }

            $existingFacility = $this->facilityService->getFacilityById($id);

            $result = $this->facilityService->deleteFacility($existingFacility);

            if (!$result) {
                $errorResponse = new NotFound(["error" => "Facility with ID $id not found."]); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $response = new NoContent(); // 204 No Content response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
            $errorResponse->send();
        }
    }
}
