<?php

namespace App\Controllers;

use App\Services\IFacilityService;
use App\Plugins\Di\Factory;

class FacilityController
{
    private $facilityService;

    public function __construct()
    {
        $this->facilityService = Factory::getDi()->getShared('facilityService');
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

    public function getAllFacilities()
    {
        try {
            $facilities = $this->facilityService->getAllFacilities();
            return $this->jsonResponse($facilities);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse($e->getMessage(), 500);
        }
    }

    public function getFacilityById($id)
    {
        try {
            $facility = $this->facilityService->getFacilityById($id);
            return $this->jsonResponse($facility);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse($e->getMessage(), 500);
        }
    }

    public function createFacility()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $this->facilityService->createFacility($data);
            return $this->jsonResponse($result);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse($e->getMessage(), 500);
        }
    }

    public function updateFacility($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $this->facilityService->updateFacility($id, $data);
            return $this->jsonResponse($result);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse($e->getMessage(), 500);
        }
    }

    public function deleteFacility($id)
    {
        try {
            $result = $this->facilityService->deleteFacility($id);
            return $this->jsonResponse($result);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse($e->getMessage(), 500);
        }
    }
}
