<?php

namespace App\Entity;

use App\Repository\MedicalHistoryRepository;
use App\Utils\MedicalHistoryCategory;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MedicalHistoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MedicalHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:'CUSTOM')]
    #[ORM\Column(type:'uuid', unique: true, length: 30)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['medical_history'])]
    private ?Uuid $id = null;

    #[ORM\Column(type:'uuid')]
    #[Groups(['medical_history'])]
    private ?Uuid $patientId = null;

    #[ORM\Column(type:'uuid', nullable: true)]
    #[Groups(['medical_history'])]
    private ?Uuid $doctorId = null;

    #[ORM\Column(type:'uuid', nullable: true)]
    #[Groups(['medical_history'])]
    private ?Uuid $appointmentId = null;

    #[ORM\Column(type: Types::STRING, enumType: MedicalHistoryCategory::class)]
    #[Groups(['medical_history'])]
    private ?MedicalHistoryCategory $category = null;

    #[ORM\Column(length: 255)]
    #[Groups(['medical_history'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['medical_history'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['medical_history'])]
    private ?string $diagnosis = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['medical_history'])]
    private ?string $treatment = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['medical_history'])]
    private ?array $medications = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['medical_history'])]
    private ?array $attachments = null;

    #[ORM\Column()]
    #[Groups(['medical_history'])]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column]
    #[Groups(['medical_history'])]
    private ?bool $isPrivate = false;

    #[ORM\Column( scale: 2, nullable: true)]
    #[Groups(['medical_history'])]
    private ?float $cost = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['medical_history'])]
    private ?string $insuranceNumber = null;

    #[ORM\Column]
    #[Groups(['medical_history'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['medical_history'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getPatientId(): ?Uuid
    {
        return $this->patientId;
    }

    public function setPatientId(Uuid $patientId): static
    {
        $this->patientId = $patientId;
        return $this;
    }

    public function getDoctorId(): ?Uuid
    {
        return $this->doctorId;
    }

    public function setDoctorId(?Uuid $doctorId): static
    {
        $this->doctorId = $doctorId;
        return $this;
    }

    public function getAppointmentId(): ?Uuid
    {
        return $this->appointmentId;
    }

    public function setAppointmentId(?Uuid $appointmentId): static
    {
        $this->appointmentId = $appointmentId;
        return $this;
    }

    public function getCategory(): ?MedicalHistoryCategory
    {
        return $this->category;
    }

    public function setCategory(MedicalHistoryCategory $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDiagnosis(): ?string
    {
        return $this->diagnosis;
    }

    public function setDiagnosis(?string $diagnosis): static
    {
        $this->diagnosis = $diagnosis;
        return $this;
    }

    public function getTreatment(): ?string
    {
        return $this->treatment;
    }

    public function setTreatment(?string $treatment): static
    {
        $this->treatment = $treatment;
        return $this;
    }

    public function getMedications(): ?array
    {
        return $this->medications;
    }

    public function setMedications(?array $medications): static
    {
        $this->medications = $medications;
        return $this;
    }

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function setAttachments(?array $attachments): static
    {
        $this->attachments = $attachments;
        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function isPrivate(): ?bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): static
    {
        $this->isPrivate = $isPrivate;
        return $this;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(?float $cost): static
    {
        $this->cost = $cost;
        return $this;
    }

    public function getInsuranceNumber(): ?string
    {
        return $this->insuranceNumber;
    }

    public function setInsuranceNumber(?string $insuranceNumber): static
    {
        $this->insuranceNumber = $insuranceNumber;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
