<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use App\Helpers\PaginationHelper;

class PaginationHelperTest extends TestCase
{
    public function testPaginateBasic(): void
    {
        $result = PaginationHelper::paginate(100, 1, 10);
        
        $this->assertEquals(100, $result['total_items']);
        $this->assertEquals(1, $result['current_page']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(10, $result['total_pages']);
        $this->assertEquals(0, $result['offset']);
    }

    public function testPaginateSecondPage(): void
    {
        $result = PaginationHelper::paginate(100, 2, 10);
        
        $this->assertEquals(100, $result['total_items']);
        $this->assertEquals(2, $result['current_page']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(10, $result['total_pages']);
        $this->assertEquals(10, $result['offset']);
    }

    public function testPaginateLastPage(): void
    {
        $result = PaginationHelper::paginate(95, 10, 10);
        
        $this->assertEquals(95, $result['total_items']);
        $this->assertEquals(10, $result['current_page']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(10, $result['total_pages']);
        $this->assertEquals(90, $result['offset']);
    }

    public function testPaginateWithRemainder(): void
    {
        $result = PaginationHelper::paginate(25, 3, 10);
        
        $this->assertEquals(25, $result['total_items']);
        $this->assertEquals(3, $result['current_page']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(3, $result['total_pages']); // 25/10 = 2.5 rounded up to 3
        $this->assertEquals(20, $result['offset']);
    }

    public function testPaginatePageExceedsTotal(): void
    {
        // When requested page exceeds total pages, it should be adjusted
        $result = PaginationHelper::paginate(25, 5, 10);
        
        $this->assertEquals(25, $result['total_items']);
        $this->assertEquals(3, $result['current_page']); // Adjusted to max page
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(3, $result['total_pages']);
        $this->assertEquals(20, $result['offset']);
    }

    public function testPaginateZeroPage(): void
    {
        // Page 0 should be adjusted to page 1
        $result = PaginationHelper::paginate(100, 0, 10);
        
        $this->assertEquals(100, $result['total_items']);
        $this->assertEquals(1, $result['current_page']); // Adjusted to min page
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(10, $result['total_pages']);
        $this->assertEquals(0, $result['offset']);
    }

    public function testPaginateNegativePage(): void
    {
        // Negative page should be adjusted to page 1
        $result = PaginationHelper::paginate(100, -5, 10);
        
        $this->assertEquals(100, $result['total_items']);
        $this->assertEquals(1, $result['current_page']); // Adjusted to min page
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(10, $result['total_pages']);
        $this->assertEquals(0, $result['offset']);
    }

    public function testPaginateZeroItems(): void
    {
        $result = PaginationHelper::paginate(0, 1, 10);
        
        $this->assertEquals(0, $result['total_items']);
        $this->assertEquals(1, $result['current_page']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(0, $result['total_pages']);
        $this->assertEquals(0, $result['offset']);
    }

    public function testPaginateLargePerPage(): void
    {
        $result = PaginationHelper::paginate(5, 1, 100);
        
        $this->assertEquals(5, $result['total_items']);
        $this->assertEquals(1, $result['current_page']);
        $this->assertEquals(100, $result['per_page']);
        $this->assertEquals(1, $result['total_pages']);
        $this->assertEquals(0, $result['offset']);
    }

    public function testPaginateExactDivision(): void
    {
        // Test when total items divides exactly by per_page
        $result = PaginationHelper::paginate(100, 5, 20);
        
        $this->assertEquals(100, $result['total_items']);
        $this->assertEquals(5, $result['current_page']);
        $this->assertEquals(20, $result['per_page']);
        $this->assertEquals(5, $result['total_pages']); // 100/20 = 5 exactly
        $this->assertEquals(80, $result['offset']);
    }

    public function testPaginateZeroPerPage(): void
    {
        // When per_page is 0, it should default to 10
        $result = PaginationHelper::paginate(100, 1, 0);
        
        $this->assertEquals(100, $result['total_items']);
        $this->assertEquals(1, $result['current_page']);
        $this->assertEquals(10, $result['per_page']); // Adjusted to default
        $this->assertEquals(10, $result['total_pages']);
        $this->assertEquals(0, $result['offset']);
    }

    public function testPaginateNegativePerPage(): void
    {
        // When per_page is negative, it should default to 10
        $result = PaginationHelper::paginate(100, 1, -5);
        
        $this->assertEquals(100, $result['total_items']);
        $this->assertEquals(1, $result['current_page']);
        $this->assertEquals(10, $result['per_page']); // Adjusted to default
        $this->assertEquals(10, $result['total_pages']);
        $this->assertEquals(0, $result['offset']);
    }

    public function testPaginateNegativeTotalItems(): void
    {
        // When total_items is negative, it should be adjusted to 0
        $result = PaginationHelper::paginate(-50, 1, 10);
        
        $this->assertEquals(0, $result['total_items']); // Adjusted to 0
        $this->assertEquals(1, $result['current_page']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(0, $result['total_pages']);
        $this->assertEquals(0, $result['offset']);
    }
}