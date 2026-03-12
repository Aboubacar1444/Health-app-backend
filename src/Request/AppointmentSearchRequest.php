<?php

namespace App\Request;

class AppointmentSearchRequest
{
    public ?string $patientId = null;
    public ?string $doctorId = null;
    public ?string $establishmentId = null;
    public ?string $status = null;
    public ?string $priority = null;
    public ?bool $isEmergency = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
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