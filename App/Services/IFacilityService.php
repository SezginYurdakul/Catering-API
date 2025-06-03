<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Facility;

interface IFacilityService
{
    /**
     * Get facilities with pagination and optional filters.
     * Retrieves a paginated list of facilities, optionally filtered by a query string and additional filters.
     *
     * @param int $page
     * @param int $perPage
     * @param string|null $query
     * @param array $filters
     * @param string $operator
     * @return array
     */
    public function getFacilities(
        int $page,
        int $perPage,
        ?string $query = null,
        array $filters = [],
        string $operator = 'AND'
    ): array;

    /**
     * Get a facility by its ID.
     *
     * @param int $id
     * @return Facility
     */
    public function getFacilityById(int $id): Facility;

    /**
     * Create a new facility.
     *
     * @param Facility $facility
     * @param array $tagIds
     * @param array $tagNames
     * @return array|string
     */
    public function createFacility(Facility $facility, array $tagIds = [], array $tagNames = []): array|string;

    /**
     * Update an existing facility.
     *
     * @param Facility $facility
     * @param array $tagIds
     * @param array $tagNames
     * @return array
     */
    public function updateFacility(Facility $facility, array $tagIds = [], array $tagNames = []): array;

    /**
     * Delete a facility.
     *
     * @param Facility $facility
     * @return string
     */
    public function deleteFacility(Facility $facility): string;

    /**
     * Create facility object
     * 
     */
    public function createFacilityObject(
        int $id,
        string $name,
        $location,
        string $creationDate,
        array $tags = []
    ): Facility;
}
