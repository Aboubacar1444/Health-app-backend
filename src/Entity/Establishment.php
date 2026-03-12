<?php

namespace App\Entity;

use App\Repository\EstablishmentRepository;
use App\Utils\EstablishmentType;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EstablishmentRepository::class)]
#[HasLifecycleCallbacks]
class Establishment
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:'CUSTOM')]
    #[ORM\Column(type:'uuid', unique: true, length: 30)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['establishment'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['establishment'])]
    private ?string $name = null;

    #[ORM\Column(type: 'string', enumType: EstablishmentType::class)]
    #[Groups(['establishment'])]
    private ?EstablishmentType $type = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['establishment'])]
    private ?string $address = null;

    #[ORM\Column(length: 100)]
    #[Groups(['establishment'])]
    private ?string $city = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['establishment'])]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['establishment'])]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['establishment'])]
    private ?string $image = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    #[Groups(['establishment'])]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    #[Groups(['establishment'])]
    private ?string $longitude = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['establishment'])]
    private ?bool $isPublic = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['establishment'])]
    private ?bool $isEmergency = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['establishment'])]
    private ?array $services = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['establishment'])]
    private ?array $insurances = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['establishment'])]
    private ?array $openingHours = null;

    #[ORM\Column(type: 'decimal', precision: 3, scale: 2, nullable: true)]
    #[Groups(['establishment'])]
    private ?string $rating = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['establishment'])]
    private ?int $totalReviews = 0;

    #[ORM\Column(nullable: true)]
    #[Groups(['establishment'])]
    private ?bool $isActive = true;

    #[ORM\Column]
    #[Groups(['establishment'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['establishment'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getType(): ?EstablishmentType
    {
        return $this->type;
    }

    public function setType(EstablishmentType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(?bool $isPublic): static
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function isEmergency(): ?bool
    {
        return $this->isEmergency;
    }

    public function setIsEmergency(?bool $isEmergency): static
    {
        $this->isEmergency = $isEmergency;
        return $this;
    }

    public function getServices(): ?array
    {
        return $this->services;
    }

    public function setServices(?array $services): static
    {
        $this->services = $services;
        return $this;
    }

    public function getInsurances(): ?array
    {
        return $this->insurances;
    }

    public function setInsurances(?array $insurances): static
    {
        $this->insurances = $insurances;
        return $this;
    }

    public function getOpeningHours(): ?array
    {
        return $this->openingHours;
    }

    public function setOpeningHours(?array $openingHours): static
    {
        $this->openingHours = $openingHours;
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

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): static
    {
        $this->isActive = $isActive;
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