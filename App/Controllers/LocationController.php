<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ILocationService;
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
     * Constructor to initialize the LocationService from the DI container.
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
     */
    public function getAllLocations()
    {
        try {
            // Take the current user from the session
            $currentUser = $_SESSION['user'] ?? 'Guest';

            // Get pagination parameters from the request
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
    
            // Call the service method with pagination
            $locations = $this->locationService->getAllLocations($page, $perPage);
    
            // Send the response
            $response = new Ok(['user'=>$currentUser,'locations'=>$locations]); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError($e->getMessage()); // 500 Internal Server Error
            $errorResponse->send();
        }
    }

    /**
     * Get a specific location by its ID.
     * Sends a 200 OK response if the location is found.
     * Sends a 404 Not Found response if the location does not exist.
     * Sends a 500 Internal Server Error response in case of an exception.
     */
    public function getLocationById($id)
    {
        try {
            $currentUser = $_SESSION['user'] ?? 'Guest';

            $location = $this->locationService->getLocationById((int) $id);

            if (!$location) {
                $errorResponse = new NotFound("Location with ID $id not found."); // 404 Not Found
                $errorResponse->send();
                return;
            }

             $response = new Ok([
                'user' => $currentUser,
                'facility' => $location
            ]); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError($e->getMessage()); // 500 Internal Server Error
            $errorResponse->send();
        }
    }

    /**
     * Create a new location.
     * Sends a 201 Created response with the created location.
     * Sends a 400 Bad Request response if required fields are missing.
     * Sends a 500 Internal Server Error response in case of an exception.
     */
    public function createLocation()
    {
        try {
            $currentUser = $_SESSION['user'] ?? 'Guest';

            $data = json_decode(file_get_contents('php://input'), true);

            if (
                empty($data['city']) || empty($data['address']) || empty($data['zip_code']) ||
                empty($data['country_code']) || empty($data['phone_number'])
            ) {
                $errorResponse = new BadRequest("All fields are required."); // 400 Bad Request
                $errorResponse->send();
                return;
            }

            $location = new Location(
                0,
                $data['city'],
                $data['address'],
                $data['zip_code'],
                $data['country_code'],
                $data['phone_number']
            );

            $result = $this->locationService->createLocation($location);
            $response = new Created(['user' => $currentUser,'result'=>$result]); // 201 Created response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError($e->getMessage()); // 500 Internal Server Error
            $errorResponse->send();
        }
    }

    /**
     * Update an existing location by its ID.
     * Sends a 200 OK response with the updated location.
     * Sends a 400 Bad Request response if required fields are missing.
     * Sends a 404 Not Found response if the location does not exist.
     * Sends a 500 Internal Server Error response in case of an exception.
     */
    public function updateLocation($id)
    {
        try {
            $currentUser = $_SESSION['user'] ?? 'Guest';

            $data = json_decode(file_get_contents('php://input'), true);

            // Check if at least one field is provided
            if (
                empty($data['city']) && empty($data['address']) && empty($data['zip_code']) &&
                empty($data['country_code']) && empty($data['phone_number'])
            ) {
                $errorResponse = new BadRequest("At least one field is required to update the location."); // 400 Bad Request
                $errorResponse->send();
                return;
            }

            // Fetch the existing location to ensure it exists
            $existingLocation = $this->locationService->getLocationById((int) $id);
            if (!$existingLocation) {
                $errorResponse = new NotFound("Location with ID $id not found."); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $location = new Location(
                (int) $id,
                $data['city'] ?? $existingLocation->city,
                $data['address'] ?? $existingLocation->address,
                $data['zip_code'] ?? $existingLocation->zip_code,
                $data['country_code'] ?? $existingLocation->country_code,
                $data['phone_number'] ?? $existingLocation->phone_number
            );

            $result = $this->locationService->updateLocation($location);

            if (!$result) {
                $errorResponse = new NotFound("Location with ID $id not found."); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $response = new Ok(['user' => $currentUser,'result'=>$result]); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError($e->getMessage()); // 500 Internal Server Error
            $errorResponse->send();
        }
    }

    /**
     * Delete a location by its ID.
     * Sends a 204 No Content response if the location is successfully deleted.
     * Sends a 404 Not Found response if the location does not exist.
     * Sends a 500 Internal Server Error response in case of an exception.
     */
    public function deleteLocation($id)
    {
        try {
            $id = (int) $id;

            // Check if the location is used by any facilities
            if ($this->locationService->isLocationUsedByFacilities($id)) {
                $errorResponse = new BadRequest(
                    "Location with ID $id cannot be deleted because it is associated with one or more facilities."
                ); // 400 Bad Request
                $errorResponse->send();
                return;
            }

            $existingLocation = $this->locationService->getLocationById($id);
            $result = $this->locationService->deleteLocation($existingLocation);

            if (!$result) {
                $errorResponse = new NotFound("Location with ID $id not found."); // 404 Not Found
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
}
