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

class LocationController
{
    private ILocationService $locationService;

    /**
     * Constructor to initialize the LocationService from the DI container.
     */
    public function __construct()
    {
        $this->locationService = Factory::getDi()->getShared('locationService');
    }

    /**
     * Get all locations.
     * Sends a 200 OK response with the list of locations.
     * Sends a 500 Internal Server Error response in case of an exception.
     */
    public function getAllLocations()
    {
        try {
            $locations = $this->locationService->getAllLocations();
            $response = new Ok($locations); // 200 OK response
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
            $location = $this->locationService->getLocationById((int) $id);

            if (!$location) {
                $errorResponse = new NotFound("Location with ID $id not found."); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $response = new Ok($location); // 200 OK response
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
            $response = new Created($result); // 201 Created response
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
                (int) $id,
                $data['city'],
                $data['address'],
                $data['zip_code'],
                $data['country_code'],
                $data['phone_number']
            );

            $result = $this->locationService->updateLocation($location);

            if (!$result) {
                $errorResponse = new NotFound("Location with ID $id not found."); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $response = new Ok($result); // 200 OK response
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
