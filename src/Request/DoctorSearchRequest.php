<?php

namespace App\Request;

class DoctorSearchRequest
{
    public ?string $speciality = null;
    public ?int $minYearsOfExperience = null;
    public ?int $maxYearsOfExperience = null;
    public ?float $maxConsultationFee = null;
    public ?bool $isEmergencyAvailable = null;
    public ?float $minRating = null;
    public ?bool $isVerified = null;
    public ?string $city = null;
    public ?string $language = null;
    public ?string $search = null; // recherche globale
    public int $page = 1;
    public int $limit = 10;

    public function __construct(array $data = [])
    {
        $this->speciality = !empty($data['speciality']) ? $data['speciality'] : null;
        $this->minYearsOfExperience = isset($data['minYearsOfExperience']) && $data['minYearsOfExperience'] !== '' ? (int) $data['minYearsOfExperience'] : null;
        $this->maxYearsOfExperience = isset($data['maxYearsOfExperience']) && $data['maxYearsOfExperience'] !== '' ? (int) $data['maxYearsOfExperience'] : null;
        $this->maxConsultationFee = isset($data['maxConsultationFee']) && $data['maxConsultationFee'] !== '' ? (float) $data['maxConsultationFee'] : null;
        $this->isEmergencyAvailable = isset($data['isEmergencyAvailable']) && $data['isEmergencyAvailable'] !== '' ? (bool) $data['isEmergencyAvailable'] : null;
        $this->minRating = isset($data['minRating']) && $data['minRating'] !== '' ? (float) $data['minRating'] : null;
        $this->isVerified = isset($data['isVerified']) && $data['isVerified'] !== '' ? (bool) $data['isVerified'] : null;
        $this->city = !empty($data['city']) ? $data['city'] : null;
        $this->language = !empty($data['language']) ? $data['language'] : null;
        $this->search = !empty($data['search']) ? $data['search'] : null;
        $this->page = max(1, (int) ($data['page'] ?? 1));
        $this->limit = min(100, max(1, (int) ($data['limit'] ?? 10)));
    }
}