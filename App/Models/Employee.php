<?php

declare(strict_types=1);

namespace App\Models;

class Employee
{
    public int $id;
    public string $name;
    public string $address;
    public string $phone;
    public string $email;
    public string $created_at;
    public ?array $facilityIds;

    public function __construct(
        int $id,
        string $name,
        string $address,
        string $phone,
        string $email,
        string $created_at,
        ?array $facilityIds = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->address = $address;
        $this->phone = $phone;
        $this->email = $email;
        $this->created_at = $created_at;
        $this->facilityIds = $facilityIds ?? [];
    }
}
