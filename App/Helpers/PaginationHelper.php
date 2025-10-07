<?php

declare(strict_types=1);

namespace App\Helpers;

class PaginationHelper
{
    /**
     * Generate pagination metadata.
     *
     * @param int $totalItems Total number of items.
     * @param int $currentPage Current page number.
     * @param int $perPage Number of items per page.
     * @return array Pagination metadata.
     */
    public static function paginate(int $totalItems, int $currentPage, int $perPage): array
    {
        // Critical: Prevent division by zero
        if ($perPage <= 0) {
            $perPage = 10; // Fallback to default
        }

        // Ensure non-negative total items
        $totalItems = max(0, $totalItems);

        // Calculate total pages
        $totalPages = $totalItems > 0 ? (int) ceil($totalItems / $perPage) : 0;

        // Ensure current page is within valid bounds
        $currentPage = max(1, $totalPages > 0 ? min($currentPage, $totalPages) : 1);

        // Calculate offset
        $offset = ($currentPage - 1) * $perPage;

        return [
            'total_items' => $totalItems,
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'offset' => $offset,
        ];
    }
}
