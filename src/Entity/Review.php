<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use App\Utils\RevieweeType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:'CUSTOM')]
    #[ORM\Column(type:'uuid', unique: true, length: 30)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['review'])]
    private ?Uuid $id = null;

    #[ORM\Column(type:'uuid')]
    #[Groups(['review'])]
    private ?Uuid $reviewerId = null;

    #[ORM\Column(type: Types::STRING, enumType: RevieweeType::class)]
    #[Groups(['review'])]
    private ?RevieweeType $revieweeType = null;

    #[ORM\Column(type:'uuid')]
    #[Groups(['review'])]
    private ?Uuid $revieweeId = null;

    #[ORM\Column]
    #[Groups(['review'])]
    private ?int $rating = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['review'])]
    private ?string $comment = null;

    #[ORM\Column]
    #[Groups(['review'])]
    private ?bool $isAnonymous = false;

    #[ORM\Column]
    #[Groups(['review'])]
    private ?bool $isVerified = false;

    #[ORM\Column(type:'uuid', nullable: true)]
    #[Groups(['review'])]
    private ?Uuid $verifiedBy = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['review'])]
    private ?\DateTimeImmutable $verifiedAt = null;

    #[ORM\Column]
    #[Groups(['review'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['review'])]
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

    public function getReviewerId(): ?Uuid
    {
        return $this->reviewerId;
    }

    public function setReviewerId(Uuid $reviewerId): static
    {
        $this->reviewerId = $reviewerId;
        return $this;
    }

    public function getRevieweeType(): ?RevieweeType
    {
        return $this->revieweeType;
    }

    public function setRevieweeType(RevieweeType $revieweeType): static
    {
        $this->revieweeType = $revieweeType;
        return $this;
    }

    public function getRevieweeId(): ?Uuid
    {
        return $this->revieweeId;
    }

    public function setRevieweeId(Uuid $revieweeId): static
    {
        $this->revieweeId = $revieweeId;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }
        $this->rating = $rating;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function isAnonymous(): ?bool
    {
        return $this->isAnonymous;
    }

    public function setIsAnonymous(bool $isAnonymous): static
    {
        $this->isAnonymous = $isAnonymous;
        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getVerifiedBy(): ?Uuid
    {
        return $this->verifiedBy;
    }

    public function setVerifiedBy(?Uuid $verifiedBy): static
    {
        $this->verifiedBy = $verifiedBy;
        return $this;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?\DateTimeImmutable $verifiedAt): static
    {
        $this->verifiedAt = $verifiedAt;
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