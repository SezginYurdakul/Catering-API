<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Facility;

interface IFacilityService
{
    /**
     * Create a new facility with smart tag handling.
     *
     * @param Facility $facility
     * @param array $tags Mixed array of tag IDs (int) and tag names (string)
     * @return array
     */
    public function createFacility(Facility $facility, array $tags = []): array;

    /**
     * Update a facility with smart tag handling.
     * 
     * @param Facility $facility
     * @param array $tags Mixed array of tag IDs (int) and tag names (string)
     * @return array
     */
    public function updateFacility(Facility $facility, array $tags = []): array;

    /**
     * Get all facilities.
     * Returns all facilities as paginated result with optional filtering by name, tag, or location.
     */
    public function getFacilities(
        int $page = 1,
        int $perPage = 10,
        ?string $name = null,
        ?string $tag = null,
        ?string $city = null,
        ?string $country = null,
        string $operator = 'AND',
        array $filters = [],
        ?string $query = null
    ): array;

    /**
     * Get a facility by its ID.
     */
    public function getFacilityById(int $id): ?object;

    /**
     * Delete a facility by its ID.
     */
    public function deleteFacility(int $id): array;

    /**
     * Get facilities for a specific location.
     */
    public function getFacilitiesByLocation(int $locationId): array;

    /**
     * Get facilities for a specific tag.
     */
    public function getFacilitiesByTag(int $tagId): array;

    /**
     * Add tags to a facility.
     */
    public function addTagsToFacility(int $facilityId, array $tagIds): array;

    /**
     * Remove tags from a facility.
     */
    public function removeTagsFromFacility(int $facilityId, array $tagIds): array;

    /**
     * Get total count of facilities that match the given filters.
     */
    public function getFilteredFacilitiesCount(?string $name = null, ?string $tag = null, ?string $city = null, ?string $country = null, string $operator = 'AND'): int;

    /**
     * Get employees assigned to a specific facility with pagination.
     */
    public function getEmployeesByFacilityId(int $facilityId, int $page = 1, int $perPage = 10): array;
}
