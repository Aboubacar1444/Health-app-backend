<?php

namespace App\Entity;

use App\Repository\PublicityDoctorRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PublicityDoctorRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PublicityDoctor
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true, length: 30)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['publicity_doctor'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['publicity_doctor'])]
    private ?string $title = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['publicity_doctor'])]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 30)]
    #[Groups(['publicity_doctor'])] // DIAMOND = expired in 90 days || GOLD = expired in 60 days || SILVER = expired in 30 days
    private ?string $type = null;

    #[ORM\Column]
    #[Groups(['publicity_doctor'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
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

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
