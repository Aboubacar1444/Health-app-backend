<?php

namespace App\Request;

class PharmacySearchRequest
{
    public ?string $name = null;
    public ?string $city = null;
    public ?string $commune = null;
    public ?bool $isOpen24h = null;
    public ?bool $hasDelivery = null;
    public ?bool $isActive = null;
    public ?bool $isOnDuty = null;
    public ?bool $openOnHolidays = null;
    public ?float $minRating = null;
    public ?string $service = null;
    public ?string $search = null;
    public int $page = 1;
    public int $limit = 10;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                if ($value === '') {
                    $this->$key = null;
                } else {
                    $this->$key = $value;
                }
            }
        }
    }
}