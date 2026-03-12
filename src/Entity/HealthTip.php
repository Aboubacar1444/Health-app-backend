<?php

namespace App\Entity;

use App\Repository\HealthTipRepository;
use App\Utils\HealthTipCategory;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: HealthTipRepository::class)]
#[ORM\HasLifecycleCallbacks]
class HealthTip
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:'CUSTOM')]
    #[ORM\Column(type:'uuid', unique: true, length: 30)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['health_tip'])]
    private ?Uuid $id = null;

    #[ORM\Column(type:'uuid', nullable: true)]
    #[Groups(['health_tip'])]
    private ?Uuid $authorId = null;

    #[ORM\Column(type: Types::STRING, enumType: HealthTipCategory::class)]
    #[Groups(['health_tip'])]
    private ?HealthTipCategory $category = null;

    #[ORM\Column(length: 255)]
    #[Groups(['health_tip'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['health_tip'])]
    private ?string $content = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['health_tip'])]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['health_tip'])]
    private ?array $tags = null;

    #[ORM\Column]
    #[Groups(['health_tip'])]
    private ?bool $isFeatured = false;

    #[ORM\Column]
    #[Groups(['health_tip'])]
    private ?bool $isPublished = false;

    #[ORM\Column]
    #[Groups(['health_tip'])]
    private ?int $viewsCount = 0;

    #[ORM\Column]
    #[Groups(['health_tip'])]
    private ?int $likesCount = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['health_tip'])]
    private ?string $summary = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['health_tip'])]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column]
    #[Groups(['health_tip'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['health_tip'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->publishedAt = new \DateTimeImmutable();
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

    public function getAuthorId(): ?Uuid
    {
        return $this->authorId;
    }

    public function setAuthorId(?Uuid $authorId): static
    {
        $this->authorId = $authorId;
        return $this;
    }

    public function getCategory(): ?HealthTipCategory
    {
        return $this->category;
    }

    public function setCategory(HealthTipCategory $category): static
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
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

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): static
    {
        $this->tags = $tags;
        return $this;
    }

    public function isFeatured(): ?bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;
        return $this;
    }

    public function isPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;
        if ($isPublished && !$this->publishedAt) {
            $this->publishedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getViewsCount(): ?int
    {
        return $this->viewsCount;
    }

    public function setViewsCount(int $viewsCount): static
    {
        $this->viewsCount = $viewsCount;
        return $this;
    }

    public function incrementViewsCount(): static
    {
        $this->viewsCount++;
        return $this;
    }

    public function getLikesCount(): ?int
    {
        return $this->likesCount;
    }

    public function setLikesCount(int $likesCount): static
    {
        $this->likesCount = $likesCount;
        return $this;
    }

    public function incrementLikesCount(): static
    {
        $this->likesCount++;
        return $this;
    }

    public function decrementLikesCount(): static
    {
        if ($this->likesCount > 0) {
            $this->likesCount--;
        }
        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): static
    {
        $this->summary = $summary;
        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;
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