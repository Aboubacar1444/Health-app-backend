<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Attribute\Groups;



#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_PHONE', fields: ['phone'])]
#[HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:'CUSTOM')]
    #[ORM\Column(type:'uuid', unique: true, length: 30)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['user'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['user'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user'])]
    private ?string $fullName = null;

    #[ORM\Column(length: 20, nullable: true, unique: true)]
    #[Groups(['user'])]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user'])]
    private ?string $profileImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user'])]
    private ?string $locale = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user'])]
    private ?array $meta = null;

    #[ORM\Column(length: 75, nullable: true)]
    #[Groups(['user'])]
    private ?string $userJob = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user'])]
    private ?bool $isActivated = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user'])]
    private ?string $address = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user'])]
    private ?array $documents = null;       

    #[ORM\Column(nullable: true)]
    #[Groups(['user'])]
    private ?bool $isDocVerified = null;

    

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user'])]
    private ?string $docVerifiedBy = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user'])]
    private ?bool $isAccountVerified = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user'])]
    private ?\DateTimeImmutable $docVerifiedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user'])]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user'])]
    private ?\DateTimeImmutable $phoneVerifiedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user'])]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): static
    {
        $this->fullName = $fullName;

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

    public function getProfileImage(): ?string
    {
        return $this->profileImage;
    }

    public function setProfileImage(?string $profileImage): static
    {
        $this->profileImage = $profileImage;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function setMeta(?array $meta): static
    {
        $this->meta = $meta;

        return $this;
    }

    public function getUserJob(): ?string
    {
        return $this->userJob;
    }

    public function setUserJob(?string $userJob): static
    {
        $this->userJob = $userJob;

        return $this;
    }

    public function isActivated(): ?bool
    {
        return $this->isActivated;
    }

    public function setIsActivated(?bool $isActivated): static
    {
        $this->isActivated = $isActivated;

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

    public function getDocuments(): ?array
    {
        return $this->documents;
    }

    public function setDocuments(?array $documents): static
    {
        $this->documents = $documents;

        return $this;
    }

    public function isIsDocVerified(): ?bool
    {
        return $this->isDocVerified;
    }

    public function setIsDocVerified(?bool $isDocVerified): static
    {
        $this->isDocVerified = $isDocVerified;
    
        return $this;
    }

    

    public function getDocVerifiedBy(): ?string
    {
        return $this->docVerifiedBy;
    }

    public function setDocVerifiedBy(?string $docVerifiedBy): static
    {
        $this->docVerifiedBy = $docVerifiedBy;

        return $this;
    }

    public function isAccountVerified(): ?bool
    {
        return $this->isAccountVerified;
    }

    public function setIsAccountVerified(?bool $isAccountVerified): static
    {
        $this->isAccountVerified = $isAccountVerified;

        return $this;
    }

    public function getDocVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->docVerifiedAt;
    }

    public function setDocVerifiedAt(?\DateTimeImmutable $docVerifiedAt): static
    {
        $this->docVerifiedAt = $docVerifiedAt;

        return $this;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTimeImmutable $emailVerifiedAt): static
    {
        $this->emailVerifiedAt = $emailVerifiedAt;

        return $this;
    }

    public function getPhoneVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->phoneVerifiedAt;
    }

    public function setPhoneVerifiedAt(?\DateTimeImmutable $phoneVerifiedAt): static
    {
        $this->phoneVerifiedAt = $phoneVerifiedAt;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

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
