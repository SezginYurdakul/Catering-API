<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Location;

interface ILocationService
{
    /**
     * Get all locations.
     * Retrieves a paginated list of all locations.
     * 
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getAllLocations(int $page, int $perPage): array;

    /**
     * Get a location by its ID.
     * Retrieves a specific location by its unique ID.
     *
     * @param int $id
     * @return Location
     */
    public function getLocationById(int $id): ?Location;

    /**
     * Create a new location.
     * Adds a new location to the database.
     *
     * @param Location $location
     * @return array
     */
    public function createLocation(Location $location): array;

    /**
     * Update an existing location.
     * Updates the details of an existing location in the database.
     *
     * @param Location $location
     * @return array
     */
    public function updateLocation(Location $location): array;

    /**
     * Delete a location.
     * Removes a location from the database.
     *
     * @param int $id
     * @return string
     */
    public function deleteLocation(int $id): string;

    /**
     * Check if a location is used by any facilities.
     * Determines if a location is associated with any facilities in the database.
     *
     * @param int $locationId
     * @return bool
     */
    public function isLocationUsedByFacilities(int $locationId): bool;

    /**
     * Get the total number of locations.
     * Retrieves the total count of locations in the database.
     *
     * @return int
     */
    public function getTotalLocationsCount(): int;
}