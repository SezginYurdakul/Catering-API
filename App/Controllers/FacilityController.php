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
     * Get all facilities.
     * Sends a 200 OK response with the list of facilities.
     * Sends a 500 Internal Server Error response in case of an exception.
     * 
     * @return void
     */
    public function getAllFacilities(): void
    {
        try {
            // Take the current user from the session
            $currentUser = $_SESSION['user'] ?? 'Guest';

            // Get pagination parameters from the request
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : null;

            if ($perPage === null) {
                $totalItems = $this->facilityService->getTotalFacilitiesCount();
                $perPage = $totalItems;
            }

            // Call the service method with pagination
            $facilities = $this->facilityService->getAllFacilities($page, $perPage);

            // Send the response
            $response = new Ok([
                'user' => $currentUser,
                'facilities' => $facilities
            ]); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError($e->getMessage()); // 500 Internal Server Error
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
            // Take the current user from the session
            $currentUser = $_SESSION['user'] ?? 'Guest';

            $facility = $this->facilityService->getFacilityById((int) $id);

            if (!$facility) {
                $errorResponse = new NotFound("Facility with ID $id not found."); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $response = new Ok([
                'user' => $currentUser,
                'facility' => $facility
            ]); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError($e->getMessage()); // 500 Internal Server Error
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
            $currentUser = $_SESSION['user'] ?? 'Guest';

            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['name']) || empty($data['location_id'])) {
                $errorResponse = new BadRequest("Name and Location ID are required."); // 400 Bad Request
                $errorResponse->send();
                return;
            }

            $facility = new Facility(
                0,
                $data['name'],
                $data['location_id'],
                date('Y-m-d H:i:s'),
                $data['tags'] ?? [] // Optional tags field
            );

            $result = $this->facilityService->createFacility($facility);

            $response = new Created(['user' => $currentUser, 'result' => $result]); // 201 Created response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError($e->getMessage()); // 500 Internal Server Error
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

            $currentUser = $_SESSION['user'] ?? 'Guest';

            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data)) {
                $errorResponse = new BadRequest("At least one field (name, location_id, or tags) must be provided."); // 400 Bad Request
                $errorResponse->send();
                return;
            }

            $existingFacility = $this->facilityService->getFacilityById($id);

            $facility = new Facility(
                $id,
                $data['name'] ?? $existingFacility->name,
                $data['location_id'] ?? $existingFacility->location_id,
                $existingFacility->creation_date,
                $data['tags'] ?? []
            );

            $result = $this->facilityService->updateFacility($facility);

            if (!$result) {
                $errorResponse = new NotFound("Facility with ID $id not found."); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $response = new Ok(['user' => $currentUser, 'result' => $result]); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError($e->getMessage()); // 500 Internal Server Error
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

            $existingFacility = $this->facilityService->getFacilityById($id);

            $result = $this->facilityService->deleteFacility($existingFacility);

            if (!$result) {
                $errorResponse = new NotFound("Facility with ID $id not found."); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $response = new NoContent(); // 204 No Content response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError($e->getMessage()); // 500 Internal Server Error
            $errorResponse->send();
        }
    }

    /**
     * Search for facilities by a query string.
     * Sends a 200 OK response with the list of facilities that match the query.
     * Sends a 400 Bad Request response if the query is missing.
     * Sends a 500 Internal Server Error response in case of an exception.
     * 
     * @return void
     */ public function searchFacilities(): void
    {
        try {
            $currentUser = $_SESSION['user'] ?? 'Guest';

            $query = $_GET['query'] ?? '';
            $filter = $_GET['filter'] ?? '';

            if (empty($query)) {
                $errorResponse = new BadRequest("Search query is required."); // 400 Bad Request
                $errorResponse->send();
                return;
            }

            $facilities = $this->facilityService->searchFacilities($query, $filter);

            $response = new Ok(['user' => $currentUser, 'faclities' => $facilities]); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError($e->getMessage()); // 500 Internal Server Error
            $errorResponse->send();
        }
    }
}
