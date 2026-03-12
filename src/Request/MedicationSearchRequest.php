<?php

namespace App\Request;

class MedicationSearchRequest
{
    public ?string $name = null;
    public ?string $category = null;
    public ?bool $requiresPrescription = null;
    public ?bool $isReimbursed = null;
    public ?float $minPrice = null;
    public ?float $maxPrice = null;
    public ?string $manufacturer = null;
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