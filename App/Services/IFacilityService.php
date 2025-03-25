<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Facility;

interface IFacilityService
{
    /**
     * Get all facilities.
     *
     * @return array
     */
    public function getAllFacilities(): array;

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
     * Check if a location exists by its ID.
     *
     * @param int $locationId
     * @return void
     */
}
