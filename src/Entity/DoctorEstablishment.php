<?php

namespace App\Entity;

use App\Repository\DoctorEstablishmentRepository;
use App\Utils\DoctorEstablishmentStatus;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DoctorEstablishmentRepository::class)]
#[HasLifecycleCallbacks]
class DoctorEstablishment
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:'CUSTOM')]
    #[ORM\Column(type:'uuid', unique: true, length: 30)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['doctor_establishment'])]
    private ?Uuid $id = null;

    #[ORM\Column(type:'uuid')]
    #[Groups(['doctor_establishment'])]
    private ?Uuid $doctorId = null;

    #[ORM\Column(type:'uuid')]
    #[Groups(['doctor_establishment'])]
    private ?Uuid $establishmentId = null;

    #[ORM\Column(type: 'string', enumType: DoctorEstablishmentStatus::class)]
    #[Groups(['doctor_establishment'])]
    private ?DoctorEstablishmentStatus $status = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['doctor_establishment'])]
    private ?bool $isPrimary = false;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['doctor_establishment'])]
    private ?array $workingHours = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Groups(['doctor_establishment'])]
    private ?string $consultationFeeOverride = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['doctor_establishment'])]
    private ?string $notes = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['doctor_establishment'])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['doctor_establishment'])]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column]
    #[Groups(['doctor_establishment'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['doctor_establishment'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getDoctorId(): ?Uuid
    {
        return $this->doctorId;
    }

    public function setDoctorId(Uuid $doctorId): static
    {
        $this->doctorId = $doctorId;
        return $this;
    }

    public function getEstablishmentId(): ?Uuid
    {
        return $this->establishmentId;
    }

    public function setEstablishmentId(Uuid $establishmentId): static
    {
        $this->establishmentId = $establishmentId;
        return $this;
    }

    public function getStatus(): ?DoctorEstablishmentStatus
    {
        return $this->status;
    }

    public function setStatus(DoctorEstablishmentStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isPrimary(): ?bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(?bool $isPrimary): static
    {
        $this->isPrimary = $isPrimary;
        return $this;
    }

    public function getWorkingHours(): ?array
    {
        return $this->workingHours;
    }

    public function setWorkingHours(?array $workingHours): static
    {
        $this->workingHours = $workingHours;
        return $this;
    }

    public function getConsultationFeeOverride(): ?string
    {
        return $this->consultationFeeOverride;
    }

    public function setConsultationFeeOverride(?string $consultationFeeOverride): static
    {
        $this->consultationFeeOverride = $consultationFeeOverride;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): static
    {
        $this->createdAt = new \DateTimeImmutable();
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAt(): static
    {
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
}