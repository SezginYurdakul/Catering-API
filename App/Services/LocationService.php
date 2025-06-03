<?php

namespace App\Services;

use App\Models\Location;
use App\Repositories\LocationRepository;
use App\Helpers\InputSanitizer;
use App\Helpers\PaginationHelper;

class LocationService implements ILocationService
{
    private $locationRepository;

    public function __construct(LocationRepository $locationRepository)
    {
        $this->locationRepository = $locationRepository;
    }

    /**
     * Get all locations with pagination.
     *
     * @param int $page
     * @param int $perPage
     * @return array
     * @throws \Exception
     */
    public function getAllLocations(int $page, int $perPage): array
    {
        try {
            $offset = ($page - 1) * $perPage;
            $locationsData = $this->locationRepository->getAllLocations($perPage, $offset);

            $locations = array_map(function ($locationData) {
                return new Location(
                    $locationData['id'],
                    $locationData['city'],
                    $locationData['address'],
                    $locationData['zip_code'],
                    $locationData['country_code'],
                    $locationData['phone_number']
                );
            }, $locationsData);

            $totalItems = $this->locationRepository->getTotalLocationsCount();
            $pagination = PaginationHelper::paginate($totalItems, $page, $perPage);

            return [
                'locations' => $locations,
                'pagination' => $pagination
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to retrieve locations: " . $e->getMessage());
        }
    }

    /**
     * Get a location by ID.
     *
     * @param int $id
     * @return Location
     * @throws \Exception
     */
    public function getLocationById(int $id): Location
    {
        try {
            $locationData = $this->locationRepository->getLocationById($id);

            if (!$locationData) {
                throw new \Exception("Location with ID $id does not exist.");
            }

            return new Location(
                $locationData['id'],
                $locationData['city'],
                $locationData['address'],
                $locationData['zip_code'],
                $locationData['country_code'],
                $locationData['phone_number']
            );
        } catch (\Exception $e) {
            throw new \Exception("Failed to retrieve location with ID $id: " . $e->getMessage());
        }
    }

    /**
     * Create a new location.
     *
     * @param Location $location
     * @return array
     * @throws \Exception
     */
    public function createLocation(Location $location): array
    {
        try {
            $data = [
                ':city' => $location->city,
                ':address' => $location->address,
                ':zip_code' => $location->zip_code,
                ':country_code' => $location->country_code,
                ':phone_number' => $location->phone_number
            ];

            $lastLocationId = $this->locationRepository->createLocation($data);

            $locationObject = new Location(
                (int) $lastLocationId,
                $location->city,
                $location->address,
                $location->zip_code,
                $location->country_code,
                $location->phone_number
            );

            return [
                "message" => "Location with ID: '{$lastLocationId}' successfully created.",
                "location" => $locationObject
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to create location: " . $e->getMessage());
        }
    }

    /**
     * Update a location.
     *
     * @param Location $location
     * @return array
     * @throws \Exception
     */
    public function updateLocation(Location $location): array
    {
        try {
            $sanitizedData = InputSanitizer::sanitize([
                'city' => $location->city,
                'address' => $location->address,
                'zip_code' => $location->zip_code,
                'country_code' => $location->country_code,
                'phone_number' => $location->phone_number
            ]);

            $fields = array_filter($sanitizedData, fn($value) => !is_null($value) && $value !== '');

            if (empty($fields)) {
                throw new \Exception("No valid fields provided for update.");
            }

            $this->locationRepository->updateLocation($location->id, $fields);

            return [
                "message" => "Location with ID {$location->id} successfully updated.",
                "location" => $location
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to update location with ID {$location->id}: " . $e->getMessage());
        }
    }

    /**
     * Delete a location.
     *
     * @param int $id
     * @return string
     * @throws \Exception
     */
    public function deleteLocation(int $id): string
    {
        try {
            if ($this->locationRepository->isLocationUsedByFacilities($id)) {
                throw new \InvalidArgumentException("Location with ID $id cannot be deleted because it is associated with one or more facilities.");
            }

            $this->locationRepository->deleteLocation($id);

            return "Location with ID {$id} successfully deleted.";
        } catch (\InvalidArgumentException $e) {
            throw $e; // Re-throw specific exception
        } catch (\Exception $e) {
            throw new \Exception("Failed to delete location with ID $id: " . $e->getMessage());
        }
    }

    /**
     * Check if a location is used by facilities.
     *
     * @param int $locationId
     * @return bool
     * @throws \Exception
     */
    public function isLocationUsedByFacilities(int $locationId): bool
    {
        try {
            return $this->locationRepository->isLocationUsedByFacilities($locationId);
        } catch (\Exception $e) {
            throw new \Exception("Failed to check if location with ID $locationId is used by facilities: " . $e->getMessage());
        }
    }

    /**
     * Get the total count of locations.
     *
     * @return int
     * @throws \Exception
     */
    public function getTotalLocationsCount(): int
    {
        try {
            return $this->locationRepository->getTotalLocationsCount();
        } catch (\Exception $e) {
            throw new \Exception("Failed to retrieve the total count of locations: " . $e->getMessage());
        }
    }

    /**
     * Create a location object.
     * @param int $id
     * @param string $city
     * @param string $address
     * @param string $zipCode
     * @param string $countryCode
     * @param string $phoneNumber
     * @return Location 
     */
    public function createLocationObject(
        int $id,
        string $city,
        string $address,
        string $zipCode,
        string $countryCode,
        string $phoneNumber
    ): Location {
        return new Location($id, $city, $address, $zipCode, $countryCode, $phoneNumber);
    }

}