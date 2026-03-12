<?php

namespace App\Services;

use App\Entity\Doctor;
use App\Entity\Notification;
use App\Repository\DoctorRepository;
use App\Repository\UserRepository;
use App\Request\DoctorSearchRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use App\Utils\NotificationPriority;
use App\Utils\NotificationType;

final class DoctorService
{
    public function __construct(
        private readonly DoctorRepository $doctorRepository,
        private readonly UserRepository $userRepository,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
        private readonly PaginationService $paginationService,
    ) {}

    public function addDoctor(array $data): JsonResponse
    {
        if (!$data || empty($data)) {
            return $this->responsesService->errorResponse("Données invalides");
        }

        $doctor = $this->serializer->deserialize(json_encode($data), Doctor::class, 'json');
        $doctor->setUser($this->userRepository->find($data['userId']));
        $doctor = $this->entityHelper->save($doctor);

        $body = json_decode($this->serializer->serialize($doctor, 'json', ["groups" => "doctor"]), true);
        return $this->responsesService->successResponse($body, "Médecin créé avec succès");
    }

    public function getAllDoctors(?DoctorSearchRequest $searchRequest = null): JsonResponse
    {
        $queryBuilder = $this->doctorRepository->createQueryBuilder('d')
            ->leftJoin('d.user', 'u')
            ->addSelect('u');
        
        if ($searchRequest) {
            $this->applyDoctorFilters($queryBuilder, $searchRequest);
            return $this->paginationService->paginate($queryBuilder, $searchRequest->page, $searchRequest->limit, ["doctor", "user"]);
        }
        
        return $this->paginationService->paginate($queryBuilder, 1, 10, ["doctor", "user"]);
    }

    private function applyDoctorFilters($queryBuilder, DoctorSearchRequest $searchRequest): void
    {
        if ($searchRequest->speciality) {
            $queryBuilder->andWhere('d.speciality = :speciality')
                ->setParameter('speciality', $searchRequest->speciality);
        }
        
        if ($searchRequest->minYearsOfExperience) {
            $queryBuilder->andWhere('d.yearsOfExperience >= :minYears')
                ->setParameter('minYears', $searchRequest->minYearsOfExperience);
        }
        
        if ($searchRequest->maxYearsOfExperience) {
            $queryBuilder->andWhere('d.yearsOfExperience <= :maxYears')
                ->setParameter('maxYears', $searchRequest->maxYearsOfExperience);
        }
        
        if ($searchRequest->maxConsultationFee) {
            $queryBuilder->andWhere('d.consultationFee <= :maxFee')
                ->setParameter('maxFee', $searchRequest->maxConsultationFee);
        }
        
        if ($searchRequest->isEmergencyAvailable !== null) {
            $queryBuilder->andWhere('d.isEmergencyAvailable = :emergency')
                ->setParameter('emergency', $searchRequest->isEmergencyAvailable);
        }
        
        if ($searchRequest->minRating) {
            $queryBuilder->andWhere('d.rating >= :minRating')
                ->setParameter('minRating', $searchRequest->minRating);
        }
        
        if ($searchRequest->isVerified !== null) {
            $queryBuilder->andWhere('d.isVerified = :verified')
                ->setParameter('verified', $searchRequest->isVerified);
        }
        
        if ($searchRequest->language) {
            $queryBuilder->andWhere('JSON_CONTAINS(d.languages, :language) = 1')
                ->setParameter('language', json_encode($searchRequest->language));
        }
        
        if ($searchRequest->search) {
            $queryBuilder->andWhere('d.speciality LIKE :search OR d.bio LIKE :search')
                ->setParameter('search', '%' . $searchRequest->search . '%');
        }
    }

    public function getDoctorById(string $id): JsonResponse
    {
        $doctor = $this->doctorRepository->find($id);
        
        if (!$doctor) {
            return $this->responsesService->errorResponse("Médecin introuvable");
        }
        
        // Récupérer l'utilisateur associé
        if ($doctor->getUserId()) {
            $user = $this->userRepository->find($doctor->getUserId());
            if ($user) {
                $doctor->setUser($user);
            }
        }
        
        $body = json_decode($this->serializer->serialize($doctor, 'json', ["groups" => ["doctor", "user"]]), true);
        
        if ($body === null) {
            return $this->responsesService->errorResponse("Erreur de sérialisation");
        }
        
        return $this->responsesService->successResponse($body, "Médecin trouvé");
    }

    public function getDoctorByUserId(string $userId): JsonResponse
    {
        $doctor = $this->doctorRepository->findOneBy(['userId' => $userId]);
        if (!$doctor) {
            return $this->responsesService->errorResponse("Médecin introuvable");
        }
        // Récupérer l'utilisateur associé
        if ($doctor->getUserId()) {
            $user = $this->userRepository->find($doctor->getUserId());
            if ($user) {
                $doctor->setUser($user);
            }
        }
        $body = json_decode($this->serializer->serialize($doctor, 'json', ["groups" => ["doctor", "user"]]), true);
        return $this->responsesService->successResponse($body, "Médecin trouvé");
    }

    public function updateDoctor(string $id, array $data): JsonResponse
    {
        $doctor = $this->doctorRepository->find($id);
        if (!$doctor) {
            return $this->responsesService->errorResponse("Médecin introuvable");
        }

        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($doctor, $setter)) {
                $doctor->$setter($value);
            }
        }

        $doctor = $this->entityHelper->update($doctor);
        $body = json_decode($this->serializer->serialize($doctor, 'json', ["groups" => "doctor"]), true);
        return $this->responsesService->successResponse($body, "Médecin mis à jour");
    }

    public function removeDoctor(string $id): JsonResponse
    {
        $doctor = $this->doctorRepository->find($id);
        if (!$doctor) {
            return $this->responsesService->errorResponse("Médecin introuvable");
        }

        $this->entityHelper->remove($doctor);
        return $this->responsesService->successResponse([], "Médecin supprimé");
    }

    public function verifyDoctor(string $id): JsonResponse
    {
        $doctor = $this->doctorRepository->find($id);
        if (!$doctor) {
            return $this->responsesService->errorResponse("Médecin introuvable");
        }

        $doctor->setIsVerified(true);
        $doctor = $this->entityHelper->update($doctor);
        $this->createDoctorVerificationNotification($doctor);
        $body = json_decode($this->serializer->serialize($doctor, 'json', ["groups" => "doctor"]), true);
        return $this->responsesService->successResponse($body, "Médecin vérifié");
    }

    public function getVerifiedDoctors(): JsonResponse
    {
        $doctors = $this->doctorRepository->findBy(['isVerified' => true]);
        $body = json_decode($this->serializer->serialize($doctors, 'json', ["groups" => "doctor"]), true);
        return $this->responsesService->successResponse($body, "Médecins vérifiés");
    }

    public function getDoctorsBySpeciality(string $speciality): JsonResponse
    {
        $doctors = $this->doctorRepository->findBy(['speciality' => $speciality, 'isVerified' => true]);
        $body = json_decode($this->serializer->serialize($doctors, 'json', ["groups" => "doctor"]), true);
        return $this->responsesService->successResponse($body, "Médecins par spécialité");
    }

    private function createDoctorVerificationNotification(Doctor $doctor): void
    {
        if (!$doctor->getUserId()) {
            return;
        }

        $notification = new Notification();
        $notification->setUserId($doctor->getUserId())
            ->setType(NotificationType::SYSTEM)
            ->setTitle('Compte médecin vérifié')
            ->setMessage('Votre compte médecin a été vérifié avec succès.')
            ->setData([
                'doctorId' => $doctor->getId()?->toRfc4122(),
            ])
            ->setPriority(NotificationPriority::NORMAL)
            ->setIsRead(false);

        $this->entityHelper->save($notification);
    }
}
