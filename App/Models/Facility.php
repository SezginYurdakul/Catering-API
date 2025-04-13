<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\LocationService;
use App\Models\Location;

class Facility
{
    public int $id;
    public string $name;
    public string $creation_date;
    public ?array $tagIds; 
    public Location $location; 

    public function __construct(
        int $id,
        string $name,
        Location $location, 
        string $creation_date,
        ?array $tagIds = null 
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->location = $location;
        $this->creation_date = $creation_date;
        $this->tagIds = $tagIds ?? []; 
    }
}
