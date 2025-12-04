<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Facility;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Exceptions\ValidationException;
use App\Plugins\Http\Exceptions\NotFound;
use App\Services\IFacilityService;
use App\Services\ILocationService;
use App\Helpers\InputSanitizer;
use App\Helpers\Validator;

class FacilityController extends BaseController
{
    private IFacilityService $facilityService;
    private ILocationService $locationService;

    public function __construct(
        IFacilityService $facilityService,
        ILocationService $locationService,
        bool $initializeBase = true
    ) {
        if ($initializeBase) {
            parent::__construct();
        }
        $this->facilityService = $facilityService;
        $this->locationService = $locationService;
        if ($initializeBase) {
            $this->requireAuth();
        }
    }

    /**
     * Get facilities with optional pagination, search, and filtering.
     */
    public function getFacilities(): void
    {
        $errors = [];

        // Pagination validation using new validate* method
        $errors = array_merge($errors, Validator::validatePagination($_GET));

        $page = isset($_GET['page']) ? InputSanitizer::sanitizeId($_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? InputSanitizer::sanitizeId($_GET['per_page']) : 10;

        // Query parameter
        $query = isset($_GET['query']) ? InputSanitizer::sanitize(['value' => $_GET['query']])['value'] : null;
        if ($query !== null && $query === '') {
            $query = null;
        }

        // Field-specific search parameters
        $facilityName = isset($_GET['facility_name'])
            ? InputSanitizer::sanitize(['value' => $_GET['facility_name']])['value']
            : null;
        $city = isset($_GET['city'])
            ? InputSanitizer::sanitize(['value' => $_GET['city']])['value']
            : null;
        $tag = isset($_GET['tag'])
            ? InputSanitizer::sanitize(['value' => $_GET['tag']])['value']
            : null;

        // Convert empty strings to null
        foreach (['facilityName', 'city', 'tag', 'query'] as $paramName) {
            if ($$paramName === '') {
                $$paramName = null;
            }
        }

        // Filters validation
        $filters = isset($_GET['filter'])
            ? array_filter(array_map('trim', explode(',', (string) $_GET['filter'])))
            : [];

        $allowedFilters = ['facility_name', 'city', 'tag'];
        if (!empty($filters)) {
            $error = Validator::validateAllowedValues($filters, $allowedFilters, 'filter');
            if ($error) {
                $errors['filter'] = $error;
            }
        }

        // Operator validation
        $operator = isset($_GET['operator']) ? strtoupper(trim((string) $_GET['operator'])) : 'AND';
        $error = Validator::validateOperator($operator);
        if ($error) {
            $errors['operator'] = $error;
        }

        // Throw all validation errors at once
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Get facilities
        $facilitiesData = $this->facilityService->getFacilities(
            $page,
            $perPage,
            $facilityName,
            $tag,
            $city,
            null, // country parameter (not used currently)
            $operator,
            $filters,
            $query
        );

        // Validate page number against total pages
        $totalItems = $facilitiesData['pagination']['total_items'];
        $totalPages = (int) ceil($totalItems / $perPage);

        if ($totalPages > 0 && $page > $totalPages) {
            throw new ValidationException([
                'page' => "The requested page ($page) exceeds the total number of pages ($totalPages)."
            ]);
        }

        // Send response
        $response = new Ok([
            'facilities' => $facilitiesData['facilities'],
            'pagination' => $facilitiesData['pagination']
        ]);
        $response->send();
    }

    /**
     * Get a specific facility by its ID.
     */
    public function getFacilityById(int $id): void
    {
        $errors = [];

        // ID validation
        $error = Validator::validatePositiveInt($id, 'id');
        if ($error) {
            $errors['id'] = $error;
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $facility = $this->facilityService->getFacilityById($id);

        if (!$facility) {
            throw new NotFound('', 'Facility', (string) $id);
        }

        $response = new Ok(['facility' => $facility]);
        $response->send();
    }

    /**
     * Create a new facility.
     */
    public function createFacility(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $errors = [];

        // Validate required fields using new validate* method
        $errors = array_merge($errors, Validator::validateRequired($data, ['name', 'location_id']));

        // If required fields missing, throw immediately
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Sanitize inputs
        $data = InputSanitizer::sanitize($data);

        // Validate location_id is a positive integer
        $error = Validator::validatePositiveInt($data['location_id'], 'location_id');
        if ($error) {
            $errors['location_id'] = $error;
        }

        // Validate location exists
        if (!isset($errors['location_id'])) {
            $locationId = (int)$data['location_id'];
            $location = $this->locationService->getLocationById($locationId);
            if (!$location) {
                $errors['location_id'] = 'Location not found';
            }
        }

        // Handle smart tags - can be mixed array of tag IDs and names
        $tags = $data['tags'] ?? [];

        // Legacy support for separate tagIds and tagNames
        if (empty($tags)) {
            $tagIds = $data['tagIds'] ?? [];
            $tagNames = $data['tagNames'] ?? [];

            if (!empty($tagIds) && !empty($tagNames)) {
                $errors['tags'] = "Cannot provide both 'tagIds' and 'tagNames'. Use 'tags' array instead.";
            } else {
                $tags = array_merge($tagIds, $tagNames);
            }
        }

        // Validate tags is an array
        if (isset($data['tags']) && !is_array($data['tags'])) {
            $errors['tags'] = 'Tags must be an array';
        }

        // Throw all validation errors
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Create facility
        $facility = new Facility(0, $data['name'], $location, date('Y-m-d H:i:s'), []);
        $result = $this->facilityService->createFacility($facility, $tags);

        $response = new Created($result);
        $response->send();
    }

    /**
     * Update an existing facility by its ID.
     */
    public function updateFacility(int $id): void
    {
        $errors = [];

        // ID validation
        $error = Validator::validatePositiveInt($id, 'id');
        if ($error) {
            throw new ValidationException(['id' => $error]);
        }

        // Get existing facility
        $existingFacility = $this->facilityService->getFacilityById($id);
        if (!$existingFacility) {
            throw new NotFound('', 'Facility', (string) $id);
        }

        // Get request data
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        // Check if at least one field provided
        $updatableFields = ['name', 'location_id', 'tags', 'tagIds', 'tagNames'];
        $providedFields = array_intersect($updatableFields, array_keys($data));

        if (empty($providedFields)) {
            throw new ValidationException([
                'fields' => 'At least one field (name, location_id, tags) must be provided for update'
            ]);
        }

        // Sanitize data
        $data = InputSanitizer::sanitize($data);

        // Validate and sanitize name
        if (isset($data['name'])) {
            if (empty(trim($data['name']))) {
                $errors['name'] = 'Name cannot be empty';
            }
        }

        // Validate location if provided
        $location = $existingFacility->location;
        if (isset($data['location_id'])) {
            $error = Validator::validatePositiveInt($data['location_id'], 'location_id');
            if ($error) {
                $errors['location_id'] = $error;
            } else {
                $locationId = (int)$data['location_id'];
                $location = $this->locationService->getLocationById($locationId);
                if (!$location) {
                    $errors['location_id'] = 'Location not found';
                }
            }
        }

        // Handle smart tags
        $tags = $data['tags'] ?? [];

        // Legacy support for separate tagIds and tagNames
        if (empty($tags)) {
            $tagIds = $data['tagIds'] ?? [];
            $tagNames = $data['tagNames'] ?? [];

            if (!empty($tagIds) && !empty($tagNames)) {
                $errors['tags'] = "Cannot provide both 'tagIds' and 'tagNames'. Use 'tags' array instead.";
            } else {
                $tags = array_merge($tagIds, $tagNames);
            }
        }

        // Validate tags is an array
        if (isset($data['tags']) && !is_array($data['tags'])) {
            $errors['tags'] = 'Tags must be an array';
        }

        // Throw all validation errors
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Create updated facility object
        $facility = new Facility(
            $id,
            $data['name'] ?? $existingFacility->name,
            $location,
            $existingFacility->creation_date,
            []
        );

        // Update facility
        $result = $this->facilityService->updateFacility($facility, $tags);

        $response = new Ok($result);
        $response->send();
    }

    /**
     * Delete a facility by its ID.
     */
    public function deleteFacility(int $id): void
    {
        $errors = [];

        // ID validation
        $error = Validator::validatePositiveInt($id, 'id');
        if ($error) {
            $errors['id'] = $error;
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Check if facility exists
        $existingFacility = $this->facilityService->getFacilityById($id);
        if (!$existingFacility) {
            throw new NotFound('', 'Facility', (string) $id);
        }

        // Delete facility
        $this->facilityService->deleteFacility($id);

        $response = new Ok(['message' => 'Facility deleted successfully']);
        $response->send();
    }

    /**
     * Get all employees assigned to a specific facility.
     */
    public function getFacilityEmployees(int $id): void
    {
        $errors = [];

        // ID validation
        $error = Validator::validatePositiveInt($id, 'id');
        if ($error) {
            throw new ValidationException(['id' => $error]);
        }

        // Verify facility exists
        $facility = $this->facilityService->getFacilityById($id);
        if (!$facility) {
            throw new NotFound('', 'Facility', (string) $id);
        }

        // Pagination validation
        $errors = array_merge($errors, Validator::validatePagination($_GET));
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $page = isset($_GET['page']) ? InputSanitizer::sanitizeId($_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? InputSanitizer::sanitizeId($_GET['per_page']) : 10;

        // Get employees for this facility
        $employeesData = $this->facilityService->getEmployeesByFacilityId($id, $page, $perPage);

        $response = new Ok([
            'facility_id' => $id,
            'employees' => $employeesData['employees'],
            'pagination' => $employeesData['pagination']
        ]);
        $response->send();
    }
}
