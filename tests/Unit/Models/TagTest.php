<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Tag;

class TagTest extends TestCase
{
    public function testTagCreation(): void
    {
        $tag = new Tag(1, 'Wedding');

        $this->assertEquals(1, $tag->id);
        $this->assertEquals('Wedding', $tag->name);
    }

    public function testTagCreationWithEmptyName(): void
    {
        $tag = new Tag(2, '');

        $this->assertEquals(2, $tag->id);
        $this->assertEquals('', $tag->name);
    }

    public function testTagCreationWithSpecialCharacters(): void
    {
        $tag = new Tag(3, 'Corporate & Events');

        $this->assertEquals(3, $tag->id);
        $this->assertEquals('Corporate & Events', $tag->name);
    }

    public function testTagCreationWithUnicodeCharacters(): void
    {
        $tag = new Tag(4, 'Café & Restaurant');

        $this->assertEquals(4, $tag->id);
        $this->assertEquals('Café & Restaurant', $tag->name);
    }

    public function testTagCreationWithLongName(): void
    {
        $longName = str_repeat('A', 255);
        $tag = new Tag(5, $longName);

        $this->assertEquals(5, $tag->id);
        $this->assertEquals($longName, $tag->name);
    }

    public function testTagPropertiesArePublic(): void
    {
        $tag = new Tag(1, 'Original Name');
        
        // Test that we can modify properties directly
        $tag->id = 999;
        $tag->name = 'Modified Name';

        $this->assertEquals(999, $tag->id);
        $this->assertEquals('Modified Name', $tag->name);
    }

    public function testTagCreationWithZeroId(): void
    {
        $tag = new Tag(0, 'Conference');

        $this->assertEquals(0, $tag->id);
        $this->assertEquals('Conference', $tag->name);
    }

    public function testTagCreationWithNegativeId(): void
    {
        $tag = new Tag(-1, 'Outdoor');

        $this->assertEquals(-1, $tag->id);
        $this->assertEquals('Outdoor', $tag->name);
    }
}