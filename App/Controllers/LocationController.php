<?php

namespace App\Controllers;

use App\Services\ILocationService;
use App\Models\Location;
use App\Plugins\Di\Factory;

class LocationController
{
    private $locationService;

    public function __construct()
    {
        $this->locationService = Factory::getDi()->getShared('locationService');
    }

    private function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json', true, $statusCode);
        echo json_encode($data);
        exit;
    }

    private function jsonErrorResponse($message, $statusCode)
    {
        header('Content-Type: application/json', true, $statusCode);
        echo json_encode(['error' => $message]);
        exit;
    }

    public function getAllLocations()
    {
        try {
            $locations = $this->locationService->getAllLocations();
            return $this->jsonResponse($locations);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse($e->getMessage(), 500);
        }
    }

    public function getLocationById($id)
    {
        try {
            $location = $this->locationService->getLocationById((int)$id);
            return $this->jsonResponse($location);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse($e->getMessage(), 404);
        }
    }

    public function createLocation()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $location = new Location(
                0,
                $data['city'],
                $data['address'],
                $data['zip_code'],
                $data['country_code'],
                $data['phone_number']
            );
            $result = $this->locationService->createLocation($location);
            return $this->jsonResponse(['message' => $result]);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse($e->getMessage(), 400);
        }
    }

    public function updateLocation($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $location = new Location(
                (int)$id,
                $data['city'] ?? null,
                $data['address'] ?? null,
                $data['zip_code'] ?? null,
                $data['country_code'] ?? null,
                $data['phone_number'] ?? null
            );
            $result = $this->locationService->updateLocation($location);
            return $this->jsonResponse(['message' => $result]);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse($e->getMessage(), 400);
        }
    }

    public function deleteLocation($id)
    {
        try {
            $location = new Location((int)$id, '', '', '', '', '');
            $result = $this->locationService->deleteLocation($location);
            return $this->jsonResponse(['message' => $result]);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse($e->getMessage(), 400);
        }
    }
}