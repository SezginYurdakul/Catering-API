<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Location;

interface ILocationService
{
    /**
     * Get all locations.
     * Retrieves a list of all locations.
     * 
     *@param int $page 
     *@param int $perPage
     * @return array An array of Location objects.
     */
    public function getAllLocations(int $page, int $perPage): array;

    /**
     * Get a location by its ID.
     * Retrieves a specific location by its unique ID.
     *
     * @param int $id The ID of the location to retrieve.
     * @return Location The Location object corresponding to the given ID.
     */
    public function getLocationById(int $id): Location;

    /**
     * Create a new location.
     * Adds a new location to the database.
     *
     * @param Location $location The Location object containing the details of the new location.
     * @return string A success message indicating the location was created.
     */
    public function createLocation(Location $location): string;

    /**
     * Update an existing location.
     * Updates the details of an existing location in the database.
     *
     * @param Location $location The Location object containing the updated details.
     * @return string A success message indicating the location was updated.
     */
    public function updateLocation(Location $location): string;

    /**
     * Delete a location.
     * Removes a location from the database.
     *
     * @param Location $location The Location object representing the location to delete.
     * @return string A success message indicating the location was deleted.
     */
    public function deleteLocation(Location $location): string;


    /**
     * Check if a location is used by any facilities.
     * Determines if a location is associated with any facilities in the database.
     *
     * @param int $locationId The ID of the location to check.
     * @return bool True if the location is used by any facilities, false otherwise.
     */
    public function isLocationUsedByFacilities(int $locationId): bool;

    /**
     * Get the total number of locations.
     *
     * @return int The total number of locations.
     */
    public function getTotalLocationsCount(): int;
}
