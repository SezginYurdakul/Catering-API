<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Facility;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Exceptions\ValidationException;
use App\Plugins\Http\Exceptions\NotFound;
use App\Plugins\Http\Exceptions\InternalServerError;
use App\Services\IFacilityService;
use App\Helpers\InputSanitizer;
use App\Helpers\Validator;

class FacilityController extends BaseController
{
    private IFacilityService $facilityService;

    /**
     * Constructor to initialize the FacilityService from the DI container and the AuthMiddleware.
     */
    public function __construct()
    {
        parent::__construct();
        $this->facilityService = $this->getService('facilityService');
        $this->requireAuth();
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
            // Validate pagination parameters
            Validator::pagination($_GET);
            
            // Get and sanitize pagination parameters
            $page = isset($_GET['page']) ? InputSanitizer::sanitizeId($_GET['page']) : 1;
            $perPage = isset($_GET['per_page']) ? InputSanitizer::sanitizeId($_GET['per_page']) : 10;

            // Sanitize and validate query
            $query = isset($_GET['query']) ? InputSanitizer::sanitize(['value' => $_GET['query']])['value'] : null;

            // Enhanced field-specific search parameters
            $facilityName = isset($_GET['facility_name']) ? InputSanitizer::sanitize(['value' => $_GET['facility_name']])['value'] : null;
            $city = isset($_GET['city']) ? InputSanitizer::sanitize(['value' => $_GET['city']])['value'] : null;
            $tag = isset($_GET['tag']) ? InputSanitizer::sanitize(['value' => $_GET['tag']])['value'] : null;

            // Sanitize and validate filters (for backward compatibility)
            $filters = isset($_GET['filter']) ? explode(',', $_GET['filter']) : [];
            if (!empty($filters)) {
                Validator::allowedValues($filters, ['facility_name', 'city', 'tag'], 'filter');
            }

            // Sanitize and validate operator
            // Default: AND - all specified filters must match (more precise)
            // Optional: User can specify operator=OR for broader search results
            // Note: Operator kept for future flexibility, can be removed if OR is never needed
            $operator = isset($_GET['operator']) ? strtoupper(trim($_GET['operator'])) : 'AND';
            if (!in_array($operator, ['AND', 'OR'])) {
                throw new ValidationException(['operator' => "Invalid operator. Only 'AND' or 'OR' are allowed."]);
            }

            // Call the service method with enhanced parameters  
            $facilitiesData = $this->facilityService->getFacilities(
                $page, 
                $perPage, 
                $facilityName,  // name parameter
                $tag,           // tag parameter
                $city,          // city parameter
                null,           // country parameter (not used currently)
                $operator
            );

            // Check if the requested page exceeds the total number of pages
            $totalItems = $facilitiesData['pagination']['total_items'];
            $totalPages = (int) ceil($totalItems / $perPage);
            if ($totalPages > 0 && $page > $totalPages) {
                throw new ValidationException(['page' => "The requested page ($page) exceeds the total number of pages ($totalPages)."]);
            }

            // Send the response
            $response = new Ok([
                'facilities' => $facilitiesData['facilities'],
                'pagination' => $facilitiesData['pagination']
            ]); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            throw new InternalServerError($e->getMessage());
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
            // Validate ID
            Validator::positiveInt($id, 'id');
            
            $facility = $this->facilityService->getFacilityById($id);

            $response = new Ok(['facility' => $facility]);
            $response->send();
        } catch (\Exception $e) {
            // Check if it's a "not found" type error
            if (strpos($e->getMessage(), 'does not exist') !== false) {
                throw new NotFound('', 'Facility', (string) $id);
            }
            throw new InternalServerError($e->getMessage());
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
            // Get and validate request data
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            Validator::required($data, ['name', 'location_id']);
            
            // Sanitize inputs
            $data = InputSanitizer::sanitize($data);
            
            // Validate location exists
            $locationService = $this->getService('locationService');
            $location = $locationService->getLocationById((int) $data['location_id']);
            if (!$location) {
                throw new ValidationException(['location_id' => 'Location not found']);
            }

            // Handle smart tags - can be mixed array of tag IDs and names
            $tags = $data['tags'] ?? [];
            
            // Legacy support for separate tagIds and tagNames (for backward compatibility)
            if (empty($tags)) {
                $tagIds = $data['tagIds'] ?? [];
                $tagNames = $data['tagNames'] ?? [];
                
                if (!empty($tagIds) && !empty($tagNames)) {
                    throw new ValidationException(['tags' => "Cannot provide both 'tagIds' and 'tagNames'. Use 'tags' array instead."]);
                }
                
                $tags = array_merge($tagIds, $tagNames);
            }

            // Create facility
            $facility = new Facility(0, $data['name'], $location, date('Y-m-d H:i:s'), []);
            
            // Create facility with smart tags
            $result = $this->facilityService->createFacility($facility, $tags);

            $response = new Created($result);
            $response->send();
        } catch (\Exception $e) {
            throw new InternalServerError($e->getMessage());
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
            // Validate ID
            Validator::positiveInt($id, 'id');
            
            // Get request data
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            // Check if at least one field provided
            if (empty($data['name']) && empty($data['location_id']) && empty($data['tags']) && empty($data['tagIds']) && empty($data['tagNames'])) {
                throw new ValidationException(['fields' => 'At least one field must be provided for update']);
            }

            // Get existing facility
            $existingFacility = $this->facilityService->getFacilityById($id);
            if (!$existingFacility) {
                throw new NotFound('', 'Facility', (string) $id);
            }

            // Sanitize data
            $data = InputSanitizer::sanitize($data);
            
            // Validate location if provided
            $location = $existingFacility->location;
            if (isset($data['location_id'])) {
                $locationService = $this->getService('locationService');
                $location = $locationService->getLocationById((int) $data['location_id']);
                if (!$location) {
                    throw new ValidationException(['location_id' => 'Location not found']);
                }
            }

            // Handle smart tags - can be mixed array of tag IDs and names
            $tags = $data['tags'] ?? [];
            
            // Legacy support for separate tagIds and tagNames (for backward compatibility)
            if (empty($tags)) {
                $tagIds = $data['tagIds'] ?? [];
                $tagNames = $data['tagNames'] ?? [];
                
                if (!empty($tagIds) && !empty($tagNames)) {
                    throw new ValidationException(['tags' => "Cannot provide both 'tagIds' and 'tagNames'. Use 'tags' array instead."]);
                }
                
                $tags = array_merge($tagIds, $tagNames);
            }

            // Create updated facility object
            $facility = new Facility(
                $id,
                $data['name'] ?? $existingFacility->name,
                $location,
                $existingFacility->creation_date,
                []
            );

            // Update facility with smart tags
            $result = $this->facilityService->updateFacility($facility, $tags);

            $response = new Ok($result);
            $response->send();
        } catch (\Exception $e) {
            throw new InternalServerError($e->getMessage());
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
            // Validate ID
            Validator::positiveInt($id, 'id');
            
            // Check if facility exists
            $existingFacility = $this->facilityService->getFacilityById($id);
            if (!$existingFacility) {
                throw new NotFound('', 'Facility', (string) $id);
            }

            // Delete facility
            $result = $this->facilityService->deleteFacility($id);
            
            if (!$result) {
                throw new InternalServerError('Failed to delete facility');
            }

            $response = new NoContent();
            $response->send();
        } catch (\Exception $e) {
            throw new InternalServerError($e->getMessage());
        }
    }
}
