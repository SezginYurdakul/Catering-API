<?php

namespace App\Services;

use App\Models\Facility;

interface IFacilityService
{
    public function getAllFacilities(): array;

    public function getFacilityById(int $id): Facility;

    public function createFacility(Facility $facility): string;

    public function updateFacility(Facility $facility): string;

    public function deleteFacility(Facility $facility): string;
}
