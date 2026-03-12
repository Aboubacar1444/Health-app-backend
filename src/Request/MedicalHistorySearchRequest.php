<?php

namespace App\Request;

class MedicalHistorySearchRequest
{
    public ?string $patientId = null;
    public ?string $doctorId = null;
    public ?string $appointmentId = null;
    public ?string $category = null;
    public ?string $title = null;
    public ?string $insuranceNumber = null;
    public ?bool $isPrivate = null;
    public ?float $minCost = null;
    public ?float $maxCost = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?string $search = null;
    public int $page = 1;
    public int $limit = 10;

    public function __construct(array $data = [])
    {
        $this->patientId = !empty($data['patientId']) ? (string) $data['patientId'] : null;
        $this->doctorId = !empty($data['doctorId']) ? (string) $data['doctorId'] : null;
        $this->appointmentId = !empty($data['appointmentId']) ? (string) $data['appointmentId'] : null;
        $this->category = !empty($data['category']) ? (string) $data['category'] : null;
        $this->title = !empty($data['title']) ? (string) $data['title'] : null;
        $this->insuranceNumber = !empty($data['insuranceNumber']) ? (string) $data['insuranceNumber'] : null;
        $this->isPrivate = isset($data['isPrivate']) && $data['isPrivate'] !== '' ? (bool) $data['isPrivate'] : null;
        $this->minCost = isset($data['minCost']) && $data['minCost'] !== '' ? (float) $data['minCost'] : null;
        $this->maxCost = isset($data['maxCost']) && $data['maxCost'] !== '' ? (float) $data['maxCost'] : null;
        $this->dateFrom = !empty($data['dateFrom']) ? (string) $data['dateFrom'] : null;
        $this->dateTo = !empty($data['dateTo']) ? (string) $data['dateTo'] : null;
        $this->search = !empty($data['search']) ? (string) $data['search'] : null;
        $this->page = max(1, (int) ($data['page'] ?? 1));
        $this->limit = min(100, max(1, (int) ($data['limit'] ?? 10)));
    }
}
