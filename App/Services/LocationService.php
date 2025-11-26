<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Location;
use App\Repositories\LocationRepository;
use App\Helpers\PaginationHelper;
use App\Domain\Exceptions\DatabaseException;
use App\Domain\Exceptions\ResourceInUseException;

class LocationService implements ILocationService
{
    private LocationRepository $locationRepository;

    public function __construct(LocationRepository $locationRepository)
    {
        $this->locationRepository = $locationRepository;
    }

    /**
     * Get all locations with pagination.
     * 
     * @throws DatabaseException If database operation fails
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
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Locations', $e->getMessage());
        }
    }

    /**
     * Get a location by ID.
     * Returns null if location not found.
     * 
     * @throws DatabaseException If database operation fails
     */
    public function getLocationById(int $id): ?Location
    {
        try {
            $locationData = $this->locationRepository->getLocationById($id);

            if (!$locationData) {
                return null;
            }

            return new Location(
                $locationData['id'],
                $locationData['city'],
                $locationData['address'],
                $locationData['zip_code'],
                $locationData['country_code'],
                $locationData['phone_number']
            );
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Locations', $e->getMessage(), ['id' => $id]);
        }
    }

    /**
     * Create a new location.
     * 
     * @throws DatabaseException If database operation fails
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

            if (!$lastLocationId) {
                throw new DatabaseException('INSERT', 'Locations', 'Failed to retrieve location ID');
            }

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
        } catch (\PDOException $e) {
            throw new DatabaseException('INSERT', 'Locations', $e->getMessage(), [
                'city' => $location->city,
                'country_code' => $location->country_code
            ]);
        }
    }

    /**
     * Update a location.
     * 
     * @throws DatabaseException If database operation fails
     */
    public function updateLocation(Location $location): array
    {
        try {
            $fields = [
                'city' => $location->city,
                'address' => $location->address,
                'zip_code' => $location->zip_code,
                'country_code' => $location->country_code,
                'phone_number' => $location->phone_number
            ];

            // Filter out null/empty values
            $fields = array_filter($fields, fn($value) => !is_null($value) && $value !== '');

            if (empty($fields)) {
                throw new DatabaseException('UPDATE', 'Locations', 'No valid fields provided for update', [
                    'id' => $location->id
                ]);
            }

            $result = $this->locationRepository->updateLocation($location->id, $fields);

            if (!$result) {
                throw new DatabaseException('UPDATE', 'Locations', 'Update operation returned false', [
                    'id' => $location->id
                ]);
            }

            return [
                "message" => "Location with ID {$location->id} successfully updated.",
                "location" => $location
            ];
        } catch (\PDOException $e) {
            throw new DatabaseException('UPDATE', 'Locations', $e->getMessage(), [
                'id' => $location->id
            ]);
        }
    }

    /**
     * Delete a location.
     * 
     * @throws ResourceInUseException If location is used by facilities
     * @throws DatabaseException If database operation fails
     */
    public function deleteLocation(int $id): string
    {
        try {
            // Business rule: Cannot delete location if used by facilities
            if ($this->locationRepository->isLocationUsedByFacilities($id)) {
                throw new ResourceInUseException(
                    'Location',
                    $id,
                    'one or more facilities'
                );
            }

            $result = $this->locationRepository->deleteLocation($id);

            if (!$result) {
                throw new DatabaseException('DELETE', 'Locations', 'Delete operation returned false', [
                    'id' => $id
                ]);
            }

            return "Location with ID {$id} successfully deleted.";
        } catch (ResourceInUseException $e) {
            // Re-throw domain exceptions
            throw $e;
        } catch (\PDOException $e) {
            throw new DatabaseException('DELETE', 'Locations', $e->getMessage(), ['id' => $id]);
        }
    }

    /**
     * Check if a location is used by facilities.
     * 
     * @throws DatabaseException If database operation fails
     */
    public function isLocationUsedByFacilities(int $locationId): bool
    {
        try {
            return $this->locationRepository->isLocationUsedByFacilities($locationId);
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Facilities', 'Failed to check location usage', [
                'location_id' => $locationId
            ]);
        }
    }

    /**
     * Get the total count of locations.
     * 
     * @throws DatabaseException If database operation fails
     */
    public function getTotalLocationsCount(): int
    {
        try {
            return $this->locationRepository->getTotalLocationsCount();
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Locations', 'Failed to count locations');
        }
    }
}