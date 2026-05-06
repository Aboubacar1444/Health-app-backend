<?php

namespace App\Entity;

use App\Repository\AppointmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Attribute\Groups;


#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:'CUSTOM')]
    #[ORM\Column(type:'uuid', unique: true, length: 30)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['appointment'])]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'uuid')]
    #[Groups(['appointment'])]
    private ?Uuid $patientId = null;

    #[ORM\Column(type: 'uuid')]
    #[Groups(['appointment'])]
    private ?Uuid $doctorId = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    #[Groups(['appointment'])]
    private ?Uuid $establishmentId = null;

    #[ORM\Column()]
    #[Groups(['appointment'])]
    private ?\DateTimeImmutable $appointmentDate = null;

    #[ORM\Column()]
    #[Groups(['appointment'])]
    private ?\DateTimeImmutable $appointmentTime = null;
    #[ORM\Column]
    #[Groups(['appointment'])]
    private ?int $durationMinutes = 30;

    #[ORM\Column(length: 20)]
    #[Groups(['appointment'])]
    private ?string $status = 'PENDING';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['appointment'])]
    private ?string $reason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['appointment'])]
    private ?string $notes = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Groups(['appointment'])]
    private ?string $consultationFee = null;

    #[ORM\Column]
    #[Groups(['appointment'])]
    private ?bool $isEmergency = false;

    #[ORM\Column(type: 'uuid', nullable: true)]
    #[Groups(['appointment'])]
    private ?Uuid $cancelledBy = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['appointment'])]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['appointment'])]
    private ?string $cancellationReason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['appointment'])]
    private ?string $patientSymptoms = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['appointment'])]
    private ?array $insurance = null;

    #[ORM\Column(length: 20)]
    #[Groups(['appointment'])]
    private ?string $priority = 'NORMAL';

    #[ORM\Column]
    #[Groups(['appointment'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['appointment'])]
    private ?\DateTimeImmutable $updatedAt = null;
    
    #[Groups(['appointment'])]
    private ?array $doctor = null;
    
    #[Groups(['appointment'])]
    private ?array $patient = null;
    

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function setDoctorId(Uuid $doctorId): static
    {
        $this->doctorId = $doctorId;
        return $this;
    }

    public function getEstablishmentId(): ?Uuid
    {
        return $this->establishmentId;
    }

    public function setEstablishmentId(?Uuid $establishmentId): static
    {
        $this->establishmentId = $establishmentId;
        return $this;
    }

    public function getAppointmentDate(): ?\DateTimeImmutable
    {
        return $this->appointmentDate;
    }

    public function setAppointmentDate(\DateTimeImmutable $appointmentDate): static
    {
        $this->appointmentDate = $appointmentDate;
        return $this;
    }

    public function getAppointmentTime(): ?\DateTimeImmutable
    {
        return $this->appointmentTime;
    }

    public function setAppointmentTime(\DateTimeImmutable $appointmentTime): static
    {
        $this->appointmentTime = $appointmentTime;
        return $this;
    }

    public function getDurationMinutes(): ?int
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(int $durationMinutes): static
    {
        $this->durationMinutes = $durationMinutes;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;
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

    public function getConsultationFee(): ?string
    {
        return $this->consultationFee;
    }

    public function setConsultationFee(?string $consultationFee): static
    {
        $this->consultationFee = $consultationFee;
        return $this;
    }

    public function isEmergency(): ?bool
    {
        return $this->isEmergency;
    }

    public function setIsEmergency(bool $isEmergency): static
    {
        $this->isEmergency = $isEmergency;
        return $this;
    }

    public function getCancelledBy(): ?Uuid
    {
        return $this->cancelledBy;
    }

    public function setCancelledBy(?Uuid $cancelledBy): static
    {
        $this->cancelledBy = $cancelledBy;
        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;
        return $this;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function setCancellationReason(?string $cancellationReason): static
    {
        $this->cancellationReason = $cancellationReason;
        return $this;
    }

    public function getPatientSymptoms(): ?string
    {
        return $this->patientSymptoms;
    }

    public function setPatientSymptoms(?string $patientSymptoms): static
    {
        $this->patientSymptoms = $patientSymptoms;
        return $this;
    }

    public function getInsurance(): ?array
    {
        return $this->insurance;
    }

    public function setInsurance(?array $insurance): static
    {
        $this->insurance = $insurance;
        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
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

    
    public function getDoctor(): ?array
    {
        return $this->doctor;
    }

    public function setDoctor(?array $doctor): static
    {
        $this->doctor = $doctor;
        return $this;
    }

    public function getPatient(): ?array
    {
        return $this->patient;
    }

    public function setPatient(?array $patient): static
    {
        $this->patient = $patient;
        return $this;
    }
  

}
