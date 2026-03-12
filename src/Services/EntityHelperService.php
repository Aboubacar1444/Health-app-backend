<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;

class EntityHelperService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function save(object $entity): object
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        return $entity;
    }

    public function persistWithoutFlush(object $entity): void
    {
        $this->entityManager->persist($entity);
    }
    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function clear (): void
    {
        $this->entityManager->clear();
    }

    public function update(object $entity): object
    {
        $this->entityManager->flush();
        return $entity;
    }

    public function remove(object $entity): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }

    public function saveMultiple(array $entities): array
    {
        foreach ($entities as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();
        return $entities;
    }

    public function removeMultiple(array $entities): void
    {
        foreach ($entities as $entity) {
            $this->entityManager->remove($entity);
        }
        $this->entityManager->flush();
    }
}