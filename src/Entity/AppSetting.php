<?php

namespace App\Entity;

use App\Repository\AppSettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use App\Utils\DataType;

#[ORM\Entity(repositoryClass: AppSettingRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AppSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:'CUSTOM')]
    #[ORM\Column(type:'uuid', unique: true, length: 30)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['app_setting'])]
    private ?Uuid $id = null;

    #[ORM\Column(name: 'setting_key', length: 100, unique: true)]
    #[Groups(['app_setting'])]
    private ?string $key = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['app_setting'])]
    private ?string $value = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['app_setting'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['app_setting'])]
    private ?bool $isPublic = false;

    #[ORM\Column(type: Types::STRING, enumType: DataType::class)]
    #[Groups(['app_setting'])]
    private ?DataType $dataType = DataType::STRING;

    #[ORM\Column]
    #[Groups(['app_setting'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['app_setting'])]
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

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): static
    {
        $this->key = $key;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;
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

    public function isPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function getDataType(): ?DataType
    {
        return $this->dataType;
    }

    public function setDataType(DataType $dataType): static
    {
        $this->dataType = $dataType;
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