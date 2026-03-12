<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use App\Utils\NotificationPriority;
use App\Utils\NotificationType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:'CUSTOM')]
    #[ORM\Column(type:'uuid', unique: true, length: 30)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['notification'])]
    private ?Uuid $id = null;

    #[ORM\Column(type:'uuid')]
    #[Groups(['notification'])]
    private ?Uuid $userId = null;

    #[ORM\Column(type: Types::STRING, enumType: NotificationType::class)]
    #[Groups(['notification'])]
    private ?NotificationType $type = null;

    #[ORM\Column(length: 255)]
    #[Groups(['notification'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['notification'])]
    private ?string $message = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['notification'])]
    private ?array $data = null;

    #[ORM\Column]
    #[Groups(['notification'])]
    private ?bool $isRead = false;

    #[ORM\Column(nullable: true)]
    #[Groups(['notification'])]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(type: Types::STRING, enumType: NotificationPriority::class)]
    #[Groups(['notification'])]
    private ?NotificationPriority $priority = NotificationPriority::NORMAL;

    #[ORM\Column(nullable: true)]
    #[Groups(['notification'])]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    #[Groups(['notification'])]
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

    public function setId(Uuid $id): static
    {
        $this->id = $id;
        return $this;
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

    public function getType(): ?NotificationType
    {
        return $this->type;
    }

    public function setType(NotificationType $type): static
    {
        $this->type = $type;
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function isRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        if ($isRead && !$this->readAt) {
            $this->readAt = new \DateTimeImmutable();
        }
        if (!$isRead) {
            $this->readAt = null;
        }
        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;
        return $this;
    }

    public function getPriority(): ?NotificationPriority
    {
        return $this->priority;
    }

    public function setPriority(NotificationPriority $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
