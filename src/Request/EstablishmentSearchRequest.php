<?php

namespace App\Request;

use App\Utils\EstablishmentType;

class EstablishmentSearchRequest
{
    public ?string $name = null;
    public ?EstablishmentType $type = null;
    public ?string $city = null;
    public ?bool $isPublic = null;
    public ?bool $isEmergency = null;
    public ?bool $isActive = null;
    public ?float $minRating = null;
    public ?string $service = null; // recherche dans les services
    public ?string $search = null; // recherche globale
    public int $page = 1;
    public int $limit = 10;

    public function __construct(array $data = [])
    {
        $this->name = !empty($data['name']) ? $data['name'] : null;
        $this->type = !empty($data['type']) ? EstablishmentType::tryFrom($data['type']) : null;
        $this->city = !empty($data['city']) ? $data['city'] : null;
        $this->isPublic = isset($data['isPublic']) && $data['isPublic'] !== '' ? (bool) $data['isPublic'] : null;
        $this->isEmergency = isset($data['isEmergency']) && $data['isEmergency'] !== '' ? (bool) $data['isEmergency'] : null;
        $this->isActive = isset($data['isActive']) && $data['isActive'] !== '' ? (bool) $data['isActive'] : null;
        $this->minRating = isset($data['minRating']) && $data['minRating'] !== '' ? (float) $data['minRating'] : null;
        $this->service = !empty($data['service']) ? $data['service'] : null;
        $this->search = !empty($data['search']) ? $data['search'] : null;
        $this->page = max(1, (int) ($data['page'] ?? 1));
        $this->limit = min(100, max(1, (int) ($data['limit'] ?? 10)));
    }
}