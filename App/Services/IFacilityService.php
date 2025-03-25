<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Facility;

interface IFacilityService
{
    /**
     * Get all facilities.
     *@param int $page
     *@param int $perPage
     * @return array
     */
    public function getAllFacilities(int $page, int $perPage): array;

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
     * @return string
     */
    public function createFacility(Facility $facility): string;

    /**
     * Update an existing facility.
     *
     * @param Facility $facility
     * @return string
     */
    public function updateFacility(Facility $facility): string;

    /**
     * Delete a facility.
     *
     * @param Facility $facility
     * @return string
     */
    public function deleteFacility(Facility $facility): string;

    /**
     * Search for facilities based on a query string.
     *
     * @param string $query
     * @param string $filter
     * @return array
     */
    public function searchFacilities(string $query, string $filter): array;
}
