<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Plugins\Di\Injectable;
use App\Plugins\Di\Factory;
use App\Plugins\Http\Response\BadRequest;
use App\Plugins\Http\Response\InternalServerError;
use App\Helpers\InputSanitizer;
use App\Middleware\AuthMiddleware;

abstract class BaseController extends Injectable
{
    protected $di;

    public function __construct()
    {
        $this->di = Factory::getDi();
    }

    /**
     * Initialize service from DI container
     */
    protected function getService(string $serviceName)
    {
        return $this->di->getShared($serviceName);
    }

    /**
     * Apply authentication middleware
     */
    protected function requireAuth(): void
    {
        $authMiddleware = new AuthMiddleware();
        $authMiddleware->handle();
    }

    /**
     * Get JSON input from request body
     */
    protected function getJsonInput(): array
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $response = new BadRequest(['error' => 'Invalid JSON input']);
            $response->send();
            exit;
        }
        
        return $input ?? [];
    }

    /**
     * Get and validate pagination parameters
     */
    protected function getPaginationParams(): array
    {
        $page = isset($_GET['page']) ? InputSanitizer::sanitizeId($_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? InputSanitizer::sanitizeId($_GET['per_page']) : 10;

        // Validate pagination parameters
        if ($page === null || $perPage === null || $page <= 0 || $perPage <= 0) {
            $response = new BadRequest([
                "error" => "Invalid pagination parameters. 'page' and 'per_page' must be positive integers."
            ]);
            $response->send();
            exit;
        }

        return [
            'page' => $page,
            'per_page' => $perPage
        ];
    }

    /**
     * Validate page number against total pages
     */
    protected function validatePageLimit(int $page, int $perPage, int $totalItems): void
    {
        $totalPages = (int) ceil($totalItems / $perPage);
        if ($totalPages > 0 && $page > $totalPages) {
            $response = new BadRequest([
                "error" => "The requested page ($page) exceeds the total number of pages ($totalPages)."
            ]);
            $response->send();
            exit;
        }
    }

    /**
     * Sanitize and validate ID parameter
     */
    protected function validateId($id, string $fieldName = 'ID'): int
    {
        $sanitizedId = InputSanitizer::sanitizeId($id);
        if ($sanitizedId === null) {
            $response = new BadRequest(["error" => "Invalid {$fieldName}. It must be a positive integer."]);
            $response->send();
            exit;
        }
        return $sanitizedId;
    }

    /**
     * Validate required fields in request data
     */
    protected function validateRequiredFields(array $data, array $requiredFields): void
    {
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $response = new BadRequest([
                "error" => "Missing required fields: " . implode(', ', $missingFields)
            ]);
            $response->send();
            exit;
        }
    }

    /**
     * Handle exceptions consistently
     */
    protected function handleException(\Exception $e, string $context = ''): void
    {
        error_log("Exception in {$context}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

        $response = new InternalServerError([
            'error' => 'An internal server error occurred',
            'message' => $_ENV['APP_ENV'] === 'development' ? $e->getMessage() : 'Internal error'
        ]);
        $response->send();
    }

    /**
     * Sanitize input data using InputSanitizer helper
     */
    protected function sanitizeInput(array $data): array
    {
        return InputSanitizer::sanitize($data);
    }

    /**
     * Sanitize single field
     */
    protected function sanitizeField($value, string $key): array
    {
        return InputSanitizer::sanitize([$key => $value]);
    }
}
