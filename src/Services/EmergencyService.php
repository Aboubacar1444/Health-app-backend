<?php

namespace App\Services;

use App\Entity\Emergency;
use App\Repository\EmergencyRepository;

class EmergencyService
{
    public function __construct(
        private readonly EmergencyRepository $emergencyRepository,
        private readonly EntityHelperService $entityHelper
    ) {}

    public function getQueryBuilder()
    {
        return $this->emergencyRepository->createQueryBuilder('e')
            ->orderBy('e.isSOS', 'DESC')
            ->addOrderBy('e.name', 'ASC');
    }

    public function find(string $id): ?Emergency
    {
        return $this->emergencyRepository->find($id);
    }

    public function save(Emergency $emergency): Emergency
    {
        return $this->entityHelper->save($emergency);
    }

    public function update(Emergency $emergency): Emergency
    {
        return $this->entityHelper->update($emergency);
    }

    public function remove(Emergency $emergency): void
    {
        $this->entityHelper->remove($emergency);
    }
}
