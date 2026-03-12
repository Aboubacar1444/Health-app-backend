<?php

namespace App\Entity;

use App\Entity\User;
use App\Repository\DoctorRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DoctorRepository::class)]
#[HasLifecycleCallbacks]
class Doctor
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:'CUSTOM')]
    #[ORM\Column(type:'uuid', unique: true, length: 30)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['doctor'])]
    private ?Uuid $id = null;

    #[ORM\Column(type:'uuid')]
    #[Groups(['doctor'])]
    private ?Uuid $userId = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['doctor'])]
    private ?User $user = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['doctor'])]
    private ?string $licenseNumber = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['doctor'])]
    private ?string $speciality = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['doctor'])]
    private ?int $yearsOfExperience = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Groups(['doctor'])]
    private ?string $consultationFee = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['doctor'])]
    private ?bool $isEmergencyAvailable = null;

    #[ORM\Column(type: 'decimal', precision: 3, scale: 2, nullable: true)]
    #[Groups(['doctor'])]
    private ?string $rating = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['doctor'])]
    private ?int $totalReviews = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['doctor'])]
    private ?string $bio = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['doctor'])]
    private ?array $languages = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['doctor'])]
    private ?array $availabilitySchedule = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['doctor'])]
    private ?bool $isVerified = false;

    #[ORM\Column]
    #[Groups(['doctor'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['doctor'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUserId(): ?Uuid
    {
        return $this->userId;
    }

    public function setUserId(Uuid $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getLicenseNumber(): ?string
    {
        return $this->licenseNumber;
    }

    public function setLicenseNumber(?string $licenseNumber): static
    {
        $this->licenseNumber = $licenseNumber;
        return $this;
    }

    public function getSpeciality(): ?string
    {
        return $this->speciality;
    }

    public function setSpeciality(?string $speciality): static
    {
        $this->speciality = $speciality;
        return $this;
    }

    public function getYearsOfExperience(): ?int
    {
        return $this->yearsOfExperience;
    }

    public function setYearsOfExperience(?int $yearsOfExperience): static
    {
        $this->yearsOfExperience = $yearsOfExperience;
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

    public function isEmergencyAvailable(): ?bool
    {
        return $this->isEmergencyAvailable;
    }

    public function setIsEmergencyAvailable(?bool $isEmergencyAvailable): static
    {
        $this->isEmergencyAvailable = $isEmergencyAvailable;
        return $this;
    }

    public function getRating(): ?string
    {
        return $this->rating;
    }

    public function setRating(?string $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getTotalReviews(): ?int
    {
        return $this->totalReviews;
    }

    public function setTotalReviews(?int $totalReviews): static
    {
        $this->totalReviews = $totalReviews;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
        return $this;
    }

    public function getLanguages(): ?array
    {
        return $this->languages;
    }

    public function setLanguages(?array $languages): static
    {
        $this->languages = $languages;
        return $this;
    }

    public function getAvailabilitySchedule(): ?array
    {
        return $this->availabilitySchedule;
    }

    public function setAvailabilitySchedule(?array $availabilitySchedule): static
    {
        $this->availabilitySchedule = $availabilitySchedule;
        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(?bool $isVerified): static
    {
        $this->isVerified = $isVerified;
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