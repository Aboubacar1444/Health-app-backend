<?php

namespace App\Request;

class UserSearchRequest
{
    public ?string $fullName = null;
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $userJob = null;
    public ?bool $isActivated = null;
    public ?bool $isDocVerified = null;
    public ?bool $isAccountVerified = null;
    public ?string $search = null; // recherche globale
    public int $page = 1;
    public int $limit = 10;

    public function __construct(array $data = [])
    {
        $this->fullName = !empty($data['fullName']) ? $data['fullName'] : null;
        $this->email = !empty($data['email']) ? $data['email'] : null;
        $this->phone = !empty($data['phone']) ? $data['phone'] : null;
        $this->userJob = !empty($data['userJob']) ? $data['userJob'] : null;
        $this->isActivated = isset($data['isActivated']) && $data['isActivated'] !== '' ? (bool) $data['isActivated'] : null;
        $this->isDocVerified = isset($data['isDocVerified']) && $data['isDocVerified'] !== '' ? (bool) $data['isDocVerified'] : null;
        $this->isAccountVerified = isset($data['isAccountVerified']) && $data['isAccountVerified'] !== '' ? (bool) $data['isAccountVerified'] : null;
        $this->search = !empty($data['search']) ? $data['search'] : null;
        $this->page = max(1, (int) ($data['page'] ?? 1));
        $this->limit = min(100, max(1, (int) ($data['limit'] ?? 10)));
    }
}