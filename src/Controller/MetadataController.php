<?php

namespace App\Controller;

use App\Services\ResponsesService;
use App\Utils\EstablishmentType;
use App\Utils\DoctorEstablishmentStatus;
use App\Utils\Roles;
use App\Utils\RevieweeType;
use App\Utils\HealthTipCategory;
use App\Utils\DataType;
use App\Repository\InsuranceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/metadata')]
final class MetadataController extends AbstractController
{
    public function __construct(
        private readonly ResponsesService $responsesService,
        private readonly InsuranceRepository $insuranceRepository,
    ) {}

    #[Route('/establishment-types', name: 'app_metadata_establishment_types', methods: "GET")]
    public function getEstablishmentTypes(): JsonResponse
    {
        $types = array_map(fn($case) => [
            'value' => $case->value,
            'label' => $this->getEstablishmentTypeLabel($case)
        ], EstablishmentType::cases());

        return $this->responsesService->successResponse($types, "Types d'établissements");
    }

    #[Route('/doctor-establishment-statuses', name: 'app_metadata_doctor_establishment_statuses', methods: "GET")]
    public function getDoctorEstablishmentStatuses(): JsonResponse
    {
        $statuses = array_map(fn($case) => [
            'value' => $case->value,
            'label' => $this->getDoctorEstablishmentStatusLabel($case)
        ], DoctorEstablishmentStatus::cases());

        return $this->responsesService->successResponse($statuses, "Statuts médecin-établissement");
    }

    #[Route('/user-roles', name: 'app_metadata_user_roles', methods: "GET")]
    public function getUserRoles(): JsonResponse
    {
        $roles = array_map(fn($case) => [
            'value' => $case->value,
            'label' => $this->getUserRoleLabel($case)
        ], Roles::cases());

        return $this->responsesService->successResponse($roles, "Rôles utilisateur");
    }

    #[Route('/reviewee-types', name: 'app_metadata_reviewee_types', methods: "GET")]
    public function getRevieweeTypes(): JsonResponse
    {
        $types = array_map(fn($case) => [
            'value' => $case->value,
            'label' => $this->getRevieweeTypeLabel($case)
        ], RevieweeType::cases());

        return $this->responsesService->successResponse($types, "Types d'entités évaluables");
    }

    #[Route('/health-tip-categories', name: 'app_metadata_health_tip_categories', methods: "GET")]
    public function getHealthTipCategories(): JsonResponse
    {
        $categories = array_map(fn($case) => [
            'value' => $case->value,
            'label' => $this->getHealthTipCategoryLabel($case)
        ], HealthTipCategory::cases());

        return $this->responsesService->successResponse($categories, "Catégories de conseils santé");
    }

    #[Route('/user-jobs', name: 'app_metadata_user_jobs', methods: "GET")]
    public function getUserJobs(): JsonResponse
    {
        $jobs = [
            ['value' => 'ADMIN', 'label' => 'Administrateur'],
            ['value' => 'GESTION', 'label' => 'Gestionnaire'],
            ['value' => 'BACKOFFICE', 'label' => 'Back-office'],
            ['value' => 'MEDECIN', 'label' => 'Médecin'],
            ['value' => 'PHARMACIEN', 'label' => 'Pharmacien'],
            ['value' => 'ETUDIANT', 'label' => 'Étudiant'],
            ['value' => 'AUTRE', 'label' => 'Autre'],
            ['value' => 'VISITEUR', 'label' => 'Visiteur']
        ];

        return $this->responsesService->successResponse($jobs, "Types d'emploi");
    }

    #[Route('/insurances', name: 'app_metadata_insurances', methods: "GET")]
    public function getInsurances(): JsonResponse
    {
        $insurances = $this->insuranceRepository->findBy(
            ['isActive' => true],
            ['name' => 'ASC']
        );

        $data = array_map(
            fn($insurance) => [
                'id' => (string) $insurance->getId(),
                'name' => $insurance->getName(),
                'taux' => $insurance->getTaux(),
            ],
            $insurances
        );

        return $this->responsesService->successResponse($data, "Assurances");
    }

    #[Route('/all', name: 'app_metadata_all', methods: "GET")]
    public function getAllMetadata(): JsonResponse
    {
        $metadata = [
            'establishmentTypes' => array_map(fn($case) => [
                'value' => $case->value,
                'label' => $this->getEstablishmentTypeLabel($case)
            ], EstablishmentType::cases()),
            'doctorEstablishmentStatuses' => array_map(fn($case) => [
                'value' => $case->value,
                'label' => $this->getDoctorEstablishmentStatusLabel($case)
            ], DoctorEstablishmentStatus::cases()),
            'userRoles' => array_map(fn($case) => [
                'value' => $case->value,
                'label' => $this->getUserRoleLabel($case)
            ], Roles::cases()),
            'userJobs' => [
                ['value' => 'ADMIN', 'label' => 'Administrateur'],
                ['value' => 'MEDECIN', 'label' => 'Médecin'],
                ['value' => 'PHARMACIEN', 'label' => 'Pharmacien'],
                ['value' => 'ETUDIANT', 'label' => 'Étudiant'],
                ['value' => 'AUTRE', 'label' => 'Autre'],
                ['value' => 'VISITEUR', 'label' => 'Visiteur']
            ],
            'revieweeTypes' => array_map(fn($case) => [
                'value' => $case->value,
                'label' => $this->getRevieweeTypeLabel($case)
            ], RevieweeType::cases()),
            'healthTipCategories' => array_map(fn($case) => [
                'value' => $case->value,
                'label' => $this->getHealthTipCategoryLabel($case)
            ], HealthTipCategory::cases()),
            'dataTypes' => array_map(fn($case) => [
                'value' => $case->value,
                'label' => $this->getDataTypeLabel($case)
            ], DataType::cases()),
            'insurances' => array_map(
                fn($insurance) => [
                    'id' => (string) $insurance->getId(),
                    'name' => $insurance->getName(),
                    'taux' => $insurance->getTaux(),
                ],
                $this->insuranceRepository->findBy(['isActive' => true], ['name' => 'ASC'])
            ),
        ];

        return $this->responsesService->successResponse($metadata, "Toutes les métadonnées");
    }

    private function getEstablishmentTypeLabel(EstablishmentType $type): string
    {
        return match($type) {
            EstablishmentType::HOSPITAL => 'Hôpital',
            EstablishmentType::CLINIC => 'Clinique',
            EstablishmentType::PHARMACY => 'Pharmacie',
            EstablishmentType::LABORATORY => 'Laboratoire',
            EstablishmentType::RADIOLOGY => 'Radiologie',
            EstablishmentType::PRIVATE_PRACTICE => 'Cabinet privé',
            EstablishmentType::OTHER => 'Autre'
        };
    }

    private function getDoctorEstablishmentStatusLabel(DoctorEstablishmentStatus $status): string
    {
        return match($status) {
            DoctorEstablishmentStatus::ACTIVE => 'Actif',
            DoctorEstablishmentStatus::INACTIVE => 'Inactif',
            DoctorEstablishmentStatus::PENDING => 'En attente',
            DoctorEstablishmentStatus::SUSPENDED => 'Suspendu'
        };
    }

    private function getUserRoleLabel(Roles $role): string
    {
        return match($role) {
            Roles::ROLE_ADMIN => 'Administrateur',
            Roles::ROLE_GESTION => 'Gestionnaire',
            Roles::ROLE_BACKOFFICE => 'Back-office',
            Roles::ROLE_USER => 'Utilisateur',
            Roles::ROLE_MEDECIN => 'Médecin',
            Roles::ROLE_PHARMACIEN => 'Pharmacien',
            Roles::ROLE_ETUDIANT => 'Étudiant'
        };
    }

    private function getRevieweeTypeLabel(RevieweeType $type): string
    {
        return match($type) {
            RevieweeType::DOCTOR => 'Médecin',
            RevieweeType::ESTABLISHMENT => 'Établissement'
        };
    }

    private function getHealthTipCategoryLabel(HealthTipCategory $category): string
    {
        return match($category) {
            HealthTipCategory::NUTRITION => 'Nutrition',
            HealthTipCategory::EXERCISE => 'Exercice',
            HealthTipCategory::MENTAL_HEALTH => 'Santé mentale',
            HealthTipCategory::PREVENTION => 'Prévention',
            HealthTipCategory::CHRONIC_DISEASES => 'Maladies chroniques',
            HealthTipCategory::MATERNAL_HEALTH => 'Santé maternelle',
            HealthTipCategory::CHILD_HEALTH => 'Santé infantile',
            HealthTipCategory::ELDERLY_CARE => 'Soins aux aînés',
            HealthTipCategory::GENERAL => 'Général'
        };
    }

    private function getDataTypeLabel(DataType $type): string
    {
        return match($type) {
            DataType::STRING => 'Chaîne de caractères',
            DataType::INTEGER => 'Nombre entier',
            DataType::BOOLEAN => 'Booléen',
            DataType::JSON => 'JSON',
            DataType::DECIMAL => 'Décimal',
            DataType::DATE => 'Date',
            DataType::DATETIME => 'Date et heure'
        };
    }
}
