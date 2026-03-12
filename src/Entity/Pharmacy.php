<?php

namespace App\Entity;

use App\Repository\PharmacyRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\Date;

#[ORM\Entity(repositoryClass: PharmacyRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Pharmacy
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:'CUSTOM')]
    #[ORM\Column(type:'uuid', unique: true, length: 30)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['pharmacy'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['pharmacy'])]
    private ?string $name = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['pharmacy'])]
    private ?string $address = null;

    #[ORM\Column(length: 100)]
    #[Groups(['pharmacy'])]
    private ?string $city = null;

    #[ORM\Column(length: 10)]
    #[Groups(['pharmacy'])]
    private ?string $postalCode = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['pharmacy'])]
    private ?string $phone = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    #[Groups(['pharmacy'])]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    #[Groups(['pharmacy'])]
    private ?string $longitude = null;

    #[ORM\Column]
    #[Groups(['pharmacy'])]
    private ?bool $isOpen24h = false;

    #[ORM\Column]
    #[Groups(['pharmacy'])]
    private ?bool $hasDelivery = false;

    #[ORM\Column]
    #[Groups(['pharmacy'])]
    private ?bool $isActive = true;

    #[ORM\Column(type: 'uuid', nullable: true)]
    #[Groups(['pharmacy'])]
    private ?Uuid $communeId = null;

    #[ORM\Column]
    #[Groups(['pharmacy'])]
    private ?bool $openOnHolidays = false;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['pharmacy'])]
    private ?array $openingHours = null;

    #[ORM\Column(type: 'decimal', precision: 3, scale: 2, nullable: true)]
    #[Groups(['pharmacy'])]
    private ?string $rating = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['pharmacy'])]
    private ?array $services = null;
    
    #[ORM\Column()]
    #[Groups(['pharmacy'])]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['pharmacy'])]
    private ?DateTimeImmutable $updatedAt = null;

   

    public function getId(): ?Uuid
    {
        return $this->id;
    }
    public function setId(Uuid $id): static
    {
        $this->id = $id;
        return $this;
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

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;
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

    public function isOpen24h(): ?bool
    {
        return $this->isOpen24h;
    }

    public function setIsOpen24h(bool $isOpen24h): static
    {
        $this->isOpen24h = $isOpen24h;
        return $this;
    }

    public function hasDelivery(): ?bool
    {
        return $this->hasDelivery;
    }

    public function setHasDelivery(bool $hasDelivery): static
    {
        $this->hasDelivery = $hasDelivery;
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

    public function getServices(): ?array
    {
        return $this->services;
    }

    public function setServices(?array $services): static
    {
        $this->services = $services;
        return $this;
    }

    public function getCommuneId(): ?Uuid
    {
        return $this->communeId;
    }

    public function setCommuneId(?Uuid $communeId): static
    {
        $this->communeId = $communeId;
        return $this;
    }

    public function isOpenOnHolidays(): ?bool
    {
        return $this->openOnHolidays;
    }

    public function setOpenOnHolidays(bool $openOnHolidays): static
    {
        $this->openOnHolidays = $openOnHolidays;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }
    #[ORM\PrePersist]
    public function setCreatedAt(): static
    {
        $this->createdAt = new DateTimeImmutable();
        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
    #[ORM\PreUpdate]
    public function setUpdatedAt(): static
    {
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }
}