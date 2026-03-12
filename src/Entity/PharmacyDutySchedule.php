<?php

namespace App\Entity;

use App\Repository\PharmacyDutyScheduleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PharmacyDutyScheduleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PharmacyDutySchedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:'CUSTOM')]
    #[ORM\Column(type:'uuid', unique: true, length: 30)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['duty_schedule'])]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'uuid')]
    #[Groups(['duty_schedule'])]
    private ?Uuid $pharmacyId = null;

    #[ORM\Column()]
    #[Groups(['duty_schedule'])]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column()]
    #[Groups(['duty_schedule'])]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(length: 20)]
    #[Groups(['duty_schedule'])]
    private ?string $scheduleType = 'weekly';

    #[ORM\Column]
    #[Groups(['duty_schedule'])]
    private ?bool $isActive = true;

    #[ORM\Column]
    #[Groups(['duty_schedule'])]
    private ?\DateTimeImmutable $createdAt = null;

    // public function __construct()
    // {
    //     $this->id = Uuid::v4();
    // }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }
    public function setId(Uuid $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getPharmacyId(): ?Uuid
    {
        return $this->pharmacyId;
    }

    public function setPharmacyId(Uuid $pharmacyId): static
    {
        $this->pharmacyId = $pharmacyId;
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getScheduleType(): ?string
    {
        return $this->scheduleType;
    }

    public function setScheduleType(string $scheduleType): static
    {
        $this->scheduleType = $scheduleType;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}