<?php

namespace App\Models;

class Location {
    public int $id;
    public ?string $city;
    public ?string $address;
    public ?string $zip_code;
    public ?string $country_code;
    public ?string $phone_number;

    public function __construct(
        int $id,
        ?string $city = null,
        ?string $address = null,
        ?string $zip_code = null,
        ?string $country_code = null,
        ?string $phone_number = null
    ) {
        $this->id = $id;
        $this->city = $city;
        $this->address = $address;
        $this->zip_code = $zip_code;
        $this->country_code = $country_code;
        $this->phone_number = $phone_number;
    }
}