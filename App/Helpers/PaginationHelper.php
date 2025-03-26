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
        $totalPages = (int) ceil($totalItems / $perPage);
        $currentPage = max(1, min($currentPage, $totalPages)); // Ensure current page is within bounds
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
