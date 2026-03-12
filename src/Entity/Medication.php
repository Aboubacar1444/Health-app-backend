<?php

namespace App\Entity;

use App\Repository\MedicationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MedicationRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(
    name: "medication",
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: "uniq_medication_identity",
            columns: ["normalized_dci", "dosage", "form"]
        )
    ]
)]
class Medication
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:'CUSTOM')]
    #[ORM\Column(type:'uuid', unique: true, length: 30)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['medication'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['medication'])]
    private ?string $name = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['medication'])]
    private ?string $category = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['medication'])]
    private ?string $form = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['medication'])]
    private ?string $dosage = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['medication'])]
    private ?float $price = null;

    #[ORM\Column]
    #[Groups(['medication'])]
    private ?bool $requiresPrescription = false;

    #[ORM\Column]
    #[Groups(['medication'])]
    private ?bool $isReimbursed = false;

    #[ORM\Column]
    #[Groups(['medication'])]
    private ?int $insuranceCoverage = 0;

    #[ORM\Column]
    #[Groups(['medication'])]
    private ?bool $isActive = true;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['medication'])]
    private ?string $manufacturer = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['medication'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['medication'])]
    private ?string $posologie = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['medication'])]
    private ?array $sideEffects = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['medication'])]
    private ?array $contraindications = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['medication'])]
    private ?string $activeIngredient = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['medication'])]
    private ?string $dci = null;

    #[ORM\Column(length: 255)]
    #[Groups(['medication'])]
    private ?string $normalizedDCI = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['medication'])]
    private ?string $image = null;

    #[ORM\Column]
    #[Groups(['medication'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['medication'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['medication'])]
    private ?string $cis = null;

    

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getForm(): ?string
    {
        return $this->form;
    }

    public function setForm(string $form): static
    {
        $this->form = $form;
        return $this;
    }

    public function getDosage(): ?string
    {
        return $this->dosage;
    }

    public function setDosage(string $dosage): static
    {
        $this->dosage = $dosage;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function requiresPrescription(): ?bool
    {
        return $this->requiresPrescription;
    }

    public function setRequiresPrescription(bool $requiresPrescription): static
    {
        $this->requiresPrescription = $requiresPrescription;
        return $this;
    }

    public function isReimbursed(): ?bool
    {
        return $this->isReimbursed;
    }

    public function setIsReimbursed(bool $isReimbursed): static
    {
        $this->isReimbursed = $isReimbursed;
        return $this;
    }

    public function getInsuranceCoverage(): ?int
    {
        return $this->insuranceCoverage;
    }

    public function setInsuranceCoverage(int $insuranceCoverage): static
    {
        $this->insuranceCoverage = $insuranceCoverage;
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

    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?string $manufacturer): static
    {
        $this->manufacturer = $manufacturer;
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

    public function getPosologie(): ?string
    {
        return $this->posologie;
    }

    public function setPosologie(?string $posologie): static
    {
        $this->posologie = $posologie;
        return $this;
    }

    public function getSideEffects(): ?array
    {
        return $this->sideEffects;
    }

    public function setSideEffects(?array $sideEffects): static
    {
        $this->sideEffects = $sideEffects;
        return $this;
    }

    public function getContraindications(): ?array
    {
        return $this->contraindications;
    }

    public function setContraindications(?array $contraindications): static
    {
        $this->contraindications = $contraindications;
        return $this;
    }

    public function getActiveIngredient(): ?string
    {
        return $this->activeIngredient;
    }

    public function setActiveIngredient(?string $activeIngredient): static
    {
        $this->activeIngredient = $activeIngredient;
        return $this;
    }

    public function getDci(): ?string
    {
        return $this->dci;
    }

    public function setDci(?string $dci): static
    {
        $this->dci = $dci;
        return $this;
    }

    public function getNormalizedDCI(): ?string
    {
        return $this->normalizedDCI;
    }

    public function setNormalizedDCI(?string $normalizedDCI): static
    {
        $this->normalizedDCI = $normalizedDCI;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCis(): ?string
    {
        return $this->cis;
    }

    public function setCis(?string $cis): static
    {
        $this->cis = $cis;

        return $this;
    }
}