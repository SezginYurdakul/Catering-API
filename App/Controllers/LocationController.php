<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ILocationService;
use App\Helpers\InputSanitizer;
use App\Helpers\Validator;
use App\Models\Location;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Exceptions\ValidationException;
use App\Plugins\Http\Exceptions\NotFound;

class LocationController extends BaseController
{
    private ILocationService $locationService;

    public function __construct()
    {
        parent::__construct();
        $this->locationService = $this->getService('locationService');
        $this->requireAuth();
    }

    /**
     * Get all locations with pagination.
     */
    public function getAllLocations(): void
    {
        $errors = [];

        // Pagination validation
        $errors = array_merge($errors, Validator::validatePagination($_GET));

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $page = isset($_GET['page']) ? InputSanitizer::sanitizeId($_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? InputSanitizer::sanitizeId($_GET['per_page']) : 10;

        // Get locations
        $locations = $this->locationService->getAllLocations($page, $perPage);

        // Validate page limit
        $totalItems = $locations['pagination']['total_items'];
        $totalPages = (int) ceil($totalItems / $perPage);
        
        if ($totalPages > 0 && $page > $totalPages) {
            throw new ValidationException([
                'page' => "The requested page ($page) exceeds the total number of pages ($totalPages)."
            ]);
        }

        $response = new Ok($locations);
        $response->send();
    }

    /**
     * Get a specific location by its ID.
     */
    public function getLocationById($id): void
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

        $location = $this->locationService->getLocationById((int)$id);

        if (!$location) {
            throw new NotFound('', 'Location', (string)$id);
        }

        $response = new Ok(["location" => $location]);
        $response->send();
    }

    /**
     * Create a new location.
     */
    public function createLocation(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $errors = [];

        // Validate required fields
        $requiredFields = ['city', 'address', 'zip_code', 'country_code', 'phone_number'];
        $errors = array_merge($errors, Validator::validateRequired($data, $requiredFields));

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Sanitize fields
        $data['city'] = InputSanitizer::sanitizeAddress($data['city']);
        $data['address'] = InputSanitizer::sanitizeAddress($data['address']);
        $data['zip_code'] = InputSanitizer::sanitizeAddress($data['zip_code']);
        $data['country_code'] = InputSanitizer::sanitizeAddress($data['country_code']);
        $data['phone_number'] = InputSanitizer::sanitizePhone($data['phone_number']);

        // Validate sanitized fields
        foreach (['city', 'address', 'zip_code', 'country_code'] as $field) {
            if ($data[$field] === null || empty(trim($data[$field]))) {
                $errors[$field] = ucfirst($field) . ' cannot be empty';
            }
        }

        if ($data['phone_number'] === null) {
            $errors['phone_number'] = 'Invalid phone number format';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Create location
        $location = new Location(
            0,
            $data['city'],
            $data['address'],
            $data['zip_code'],
            $data['country_code'],
            $data['phone_number']
        );

        $result = $this->locationService->createLocation($location);

        $response = new Created($result);
        $response->send();
    }

    /**
     * Update an existing location by its ID.
     */
    public function updateLocation($id): void
    {
        $errors = [];

        // ID validation
        $error = Validator::validatePositiveInt($id, 'id');
        if ($error) {
            throw new ValidationException(['id' => $error]);
        }

        // Get existing location
        $existingLocation = $this->locationService->getLocationById((int)$id);
        if (!$existingLocation) {
            throw new NotFound('', 'Location', (string)$id);
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        // Check if at least one field provided
        $updatableFields = ['city', 'address', 'zip_code', 'country_code', 'phone_number'];
        $providedFields = array_intersect($updatableFields, array_keys($data));
        
        if (empty($providedFields)) {
            throw new ValidationException([
                'fields' => 'At least one field (city, address, zip_code, country_code, phone_number) must be provided'
            ]);
        }

        // Sanitize provided fields
        if (isset($data['city'])) {
            $data['city'] = InputSanitizer::sanitizeAddress($data['city']);
            if ($data['city'] === null || empty(trim($data['city']))) {
                $errors['city'] = 'City cannot be empty';
            }
        }

        if (isset($data['address'])) {
            $data['address'] = InputSanitizer::sanitizeAddress($data['address']);
            if ($data['address'] === null || empty(trim($data['address']))) {
                $errors['address'] = 'Address cannot be empty';
            }
        }

        if (isset($data['zip_code'])) {
            $data['zip_code'] = InputSanitizer::sanitizeAddress($data['zip_code']);
            if ($data['zip_code'] === null || empty(trim($data['zip_code']))) {
                $errors['zip_code'] = 'Zip code cannot be empty';
            }
        }

        if (isset($data['country_code'])) {
            $data['country_code'] = InputSanitizer::sanitizeAddress($data['country_code']);
            if ($data['country_code'] === null || empty(trim($data['country_code']))) {
                $errors['country_code'] = 'Country code cannot be empty';
            }
        }

        if (isset($data['phone_number'])) {
            $data['phone_number'] = InputSanitizer::sanitizePhone($data['phone_number']);
            if ($data['phone_number'] === null) {
                $errors['phone_number'] = 'Invalid phone number format';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Create updated location object
        $location = new Location(
            (int)$id,
            $data['city'] ?? $existingLocation->city,
            $data['address'] ?? $existingLocation->address,
            $data['zip_code'] ?? $existingLocation->zip_code,
            $data['country_code'] ?? $existingLocation->country_code,
            $data['phone_number'] ?? $existingLocation->phone_number
        );

        $result = $this->locationService->updateLocation($location);

        $response = new Ok($result);
        $response->send();
    }

    /**
     * Delete a location by its ID.
     */
    public function deleteLocation($id): void
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

        // Check if location exists
        $existingLocation = $this->locationService->getLocationById((int)$id);
        if (!$existingLocation) {
            throw new NotFound('', 'Location', (string)$id);
        }

        // Delete location (service will check if in use)
        $this->locationService->deleteLocation((int)$id);

        $response = new Ok(['message' => 'Location deleted successfully']);
        $response->send();
    }
}