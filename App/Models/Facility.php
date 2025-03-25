<?php

declare(strict_types=1);

namespace App\Models;

class Facility
{
    public int $id;
    public string $name;
    public int $location_id;
    public string $creation_date;
    public array $tags = [];

    public function __construct(
        int $id,
        string $name,
        int $location_id,
        string $creation_date,
        array $tags
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->location_id = $location_id;
        $this->creation_date = $creation_date;
        $this->tags = $tags;
    }
}
