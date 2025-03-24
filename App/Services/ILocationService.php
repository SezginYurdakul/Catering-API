<?php

namespace App\Services;

use App\Models\Location;

interface ILocationService
{
    public function getAllLocations(): array;

    public function getLocationById(int $id): Location;

    public function createLocation(Location $location): string;

    public function updateLocation(Location $location): string;

    public function deleteLocation(Location $location): string;
}