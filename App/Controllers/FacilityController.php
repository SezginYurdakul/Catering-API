<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\IFacilityService;
use App\Helpers\InputSanitizer;
use App\Services\ILocationService;
use App\Plugins\Di\Factory;


class FacilityController extends RespondController
{
    private IFacilityService $facilityService;
    private ILocationService $locationService;

    /**
     * Constructor to initialize the FacilityService and DI container
     */
    public function __construct()
    {
       
        $this->facilityService = Factory::getDi()->getShared('facilityService');
        $this->locationService = Factory::getDi()->getShared('locationService');
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
                $this->respondBadRequest([
                    "error" => "Invalid pagination parameters. 'page' and 'per_page' must be positive integers."
                ]);
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
                    $this->respondBadRequest([
                        "error" => "Invalid filter provided: '$filter'. Valid filters are: " . implode(', ', $validFilters)
                    ]);
                    return;
                }
            }

            // Sanitize and validate operator
            $operator = isset($_GET['operator']) ? strtoupper(trim($_GET['operator'])) : 'OR';
            if (!in_array($operator, ['AND', 'OR'])) {
                $this->respondBadRequest(["error" => "Invalid operator. Only 'AND' or 'OR' are allowed."]);
                return;
            }

            // Call the service method with the provided parameters
            $facilitiesData = $this->facilityService->getFacilities($page, $perPage, $query, $filters, $operator);

            // Check if the requested page exceeds the total number of pages
            $totalItems = $facilitiesData['pagination']['total_items'];
            $totalPages = (int) ceil($totalItems / $perPage);
            if ($totalPages > 0 && $page > $totalPages) {
                $this->respondBadRequest([
                    "error" => "The requested page ($page) exceeds the total number of pages ($totalPages)."
                ]);
                return;
            }

            // Send the response
            $this->respondOk([
                'facilities' => $facilitiesData['facilities'],
                'pagination' => $facilitiesData['pagination']
            ]); // 200 OK response
        } catch (\Exception $e) {
            // Handle exceptions and send a 500 Internal Server Error response
            $this->respondInternalServerError([
                "error" => $e->getMessage()
            ]); // 500 Internal Server Error
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
                $this->respondBadRequest(["error" => "Invalid facility ID. It must be a positive integer."]);
                return;
            }
            $facility = $this->facilityService->getFacilityById((int) $id);

            if (!$facility) {
                $this->respondNotFound(["error" => "Facility with ID $id not found."]); // 404 Not Found
                return;
            }

            $this->respondOk([
                'facility' => $facility
            ]); // 200 OK response
        } catch (\Exception $e) {
            $this->respondInternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
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
                $this->respondBadRequest([
                    "error" => "The request body must include both 'name' and 'location_id' fields."
                ]);
                return;
            }

            // Sanitize inputs
            $name = InputSanitizer::sanitize(['value' => $data['name']])['value'];
            $locationId = InputSanitizer::sanitizeId($data['location_id']);
            if ($locationId === null) {
                $this->respondBadRequest(["error" => "Invalid location ID. It must be a positive integer."]);
                return;
            }

            // Validate location
            $location = $this->locationService->getLocationById($locationId);
            if (!$location) {
                $this->respondBadRequest(["error" => "Invalid Location ID. Location not found."]); // 400 Bad Request
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
            $facility = $this->facilityService->createFacilityObject(
                0,
                $name,
                $location,
                date('Y-m-d H:i:s'),
                [] // Tags will be handled in the service
            );

            // Call the service method
            $result = $this->facilityService->createFacility($facility, $tagIds, $tagNames);

            // Send the response
            $this->respondCreated($result); // 201 Created response
        } catch (\InvalidArgumentException $e) {
            // Handle specific exceptions
            $this->respondBadRequest(["error" => $e->getMessage()]); // 400 Bad Request
        } catch (\Exception $e) {
            // Handle general exceptions
            $this->respondInternalServerError(['error' => $e->getMessage()]); // 500 Internal Server Error
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
                $this->respondBadRequest(["error" => "Invalid facility ID. It must be a positive integer."]);
                return;
            }
            // Decode the request body
            $data = json_decode(file_get_contents('php://input'), true);

            // Check if at least one valid field is provided
            if (empty($data['name']) && empty($data['location_id']) && empty($data['tagIds']) && empty($data['tagNames'])) {
                $this->respondBadRequest([
                    "error" => "At least one valid field (facility name, location_id, tagIds, or tagNames) must be provided."
                ]);
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
                $location = $this->locationService->getLocationById($locationId);
                if (!$location) {
                    $this->respondBadRequest(["error" => "Invalid Location ID. Location not found."]);
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
            $facility = $this->facilityService->createFacilityObject(
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
            $this->respondOk(['result' => $result]); // 200 OK response
        } catch (\Exception $e) {
            $this->respondInternalServerError(['error' => $e->getMessage()]);
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
                $this->respondBadRequest(["error" => "Invalid facility ID. It must be a positive integer."]);
                return;
            }

            $existingFacility = $this->facilityService->getFacilityById($id);

            $result = $this->facilityService->deleteFacility($existingFacility);

            if (!$result) {
                $this->respondNotFound(["error" => "Facility with ID $id not found."]); // 404 Not Found
                return;
            }

            $this->respondNoContent(); // 204 No Content response
        } catch (\Exception $e) {
            $this->respondInternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
        }
    }
}
