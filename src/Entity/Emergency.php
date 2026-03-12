<?php

namespace App\Entity;

use App\Repository\EmergencyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EmergencyRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Emergency
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:'CUSTOM')]
    #[ORM\Column(type:'uuid', unique: true, length: 30)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['emergency'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['emergency'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['emergency'])]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['emergency'])]
    private ?string $description = null;

    #[ORM\Column(length: 100)]
    #[Groups(['emergency'])]
    private ?string $serviceType = null;

    #[ORM\Column(length: 50)]
    #[Groups(['emergency'])]
    private ?string $phone = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['emergency'])]
    private ?bool $isAvailable = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['emergency'])]
    private ?bool $isSOS = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['emergency'])]
    private ?string $responseTime = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['emergency'])]
    private ?string $location = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['emergency'])]
    private ?array $coordinates = null; // ['lat' => float, 'lng' => float]

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['emergency'])]
    private ?array $tags = null;

    #[ORM\Column]
    #[Groups(['emergency'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['emergency'])]
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
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

    public function getServiceType(): ?string
    {
        return $this->serviceType;
    }

    public function setServiceType(string $serviceType): static
    {
        $this->serviceType = $serviceType;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function isAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(?bool $isAvailable): static
    {
        $this->isAvailable = $isAvailable;
        return $this;
    }

    public function isSOS(): ?bool
    {
        return $this->isSOS;
    }

    public function setIsSOS(?bool $isSOS): static
    {
        $this->isSOS = $isSOS;
        return $this;
    }

    public function getResponseTime(): ?string
    {
        return $this->responseTime;
    }

    public function setResponseTime(?string $responseTime): static
    {
        $this->responseTime = $responseTime;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getCoordinates(): ?array
    {
        return $this->coordinates;
    }

    public function setCoordinates(?array $coordinates): static
    {
        $this->coordinates = $coordinates;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): static
    {
        $this->tags = $tags;
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
