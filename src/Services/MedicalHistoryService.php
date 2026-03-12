<?php

namespace App\Services;

use App\Entity\MedicalHistory;
use App\Entity\Notification;
use App\Repository\DoctorRepository;
use App\Repository\MedicalHistoryRepository;
use App\Request\MedicalHistorySearchRequest;
use App\Utils\MedicalHistoryCategory;
use App\Utils\NotificationPriority;
use App\Utils\NotificationType;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class MedicalHistoryService
{
    public function __construct(
        private readonly MedicalHistoryRepository $medicalHistoryRepository,
        private readonly DoctorRepository $doctorRepository,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
        private readonly PaginationService $paginationService,
    ) {}

    public function addMedicalHistory(array $data): JsonResponse
    {
        if (!$data || empty($data)) {
            return $this->responsesService->errorResponse("Données invalides");
        }

        $history = $this->serializer->deserialize(json_encode($data), MedicalHistory::class, 'json');
        $history = $this->entityHelper->save($history);
        $this->createMedicalHistoryNotifications($history, 'CREATED');

        $body = json_decode($this->serializer->serialize($history, 'json', ["groups" => "medical_history"]), true);
        return $this->responsesService->successResponse($body, "Historique médical créé avec succès");
    }

    public function getAllMedicalHistories(?MedicalHistorySearchRequest $searchRequest = null): JsonResponse
    {
        $queryBuilder = $this->medicalHistoryRepository->createQueryBuilder('mh')
            ->orderBy('mh.date', 'DESC')
            ->addOrderBy('mh.createdAt', 'DESC');

        if ($searchRequest) {
            $this->applyMedicalHistoryFilters($queryBuilder, $searchRequest);
            return $this->paginationService->paginate($queryBuilder, $searchRequest->page, $searchRequest->limit, ['medical_history']);
        }

        return $this->paginationService->paginate($queryBuilder, 1, 10, ['medical_history']);
    }

    public function getMedicalHistoryById(string $id): JsonResponse
    {
        $history = $this->medicalHistoryRepository->find($id);
        if (!$history) {
            return $this->responsesService->errorResponse("Historique médical introuvable");
        }

        $body = json_decode($this->serializer->serialize($history, 'json', ["groups" => "medical_history"]), true);
        return $this->responsesService->successResponse($body, "Historique médical trouvé");
    }

    public function updateMedicalHistory(string $id, array $data): JsonResponse
    {
        $history = $this->medicalHistoryRepository->find($id);
        if (!$history) {
            return $this->responsesService->errorResponse("Historique médical introuvable");
        }

        foreach ($data as $key => $value) {
            if ($key === 'category' && is_string($value)) {
                $value = MedicalHistoryCategory::from(strtoupper($value));
            }
            if ($key === 'date' && is_string($value)) {
                $value = new \DateTimeImmutable($value);
            }
            // patientId make it uuid from string value
            if (($key === 'patientId' || $key == "appointmentId" || $key == "doctorId") && is_string($value)) {
                $value = Uuid::fromString($value);
            }

            $setter = 'set' . ucfirst($key);
            if (method_exists($history, $setter)) {
                $history->$setter($value);
            }
        }

        $history = $this->entityHelper->update($history);
        $this->createMedicalHistoryNotifications($history, 'UPDATED');
        $body = json_decode($this->serializer->serialize($history, 'json', ["groups" => "medical_history"]), true);
        return $this->responsesService->successResponse($body, "Historique médical mis à jour");
    }

    public function deleteMedicalHistory(string $id): JsonResponse
    {
        $history = $this->medicalHistoryRepository->find($id);
        if (!$history) {
            return $this->responsesService->errorResponse("Historique médical introuvable");
        }

        $this->entityHelper->remove($history);
        return $this->responsesService->successResponse([], "Historique médical supprimé");
    }

    public function getMedicalHistoryByPatient(string $patientId, int $page = 1, int $limit = 10): JsonResponse
    {
        $queryBuilder = $this->medicalHistoryRepository->createQueryBuilder('mh')
            ->where('mh.patientId = :patientId')
            ->setParameter('patientId', $patientId, "uuid")
            ->orderBy('mh.date', 'DESC')
            ->addOrderBy('mh.createdAt', 'DESC');

        return $this->paginationService->paginate($queryBuilder, $page, $limit, ['medical_history']);
    }

    public function getMedicalHistoryByDoctor(string $doctorId, int $page = 1, int $limit = 10): JsonResponse
    {
        $queryBuilder = $this->medicalHistoryRepository->createQueryBuilder('mh')
            ->where('mh.doctorId = :doctorId')
            ->setParameter('doctorId', $doctorId, "uuid")
            ->orderBy('mh.date', 'DESC')
            ->addOrderBy('mh.createdAt', 'DESC');

        return $this->paginationService->paginate($queryBuilder, $page, $limit, ['medical_history']);
    }

    private function applyMedicalHistoryFilters(QueryBuilder $queryBuilder, MedicalHistorySearchRequest $searchRequest): void
    {
        if ($searchRequest->patientId) {
            $queryBuilder->andWhere('mh.patientId = :patientId')
                ->setParameter('patientId', $searchRequest->patientId, "uuid");
        }

        if ($searchRequest->doctorId) {
            $queryBuilder->andWhere('mh.doctorId = :doctorId')
                ->setParameter('doctorId', $searchRequest->doctorId, "uuid");
        }

        if ($searchRequest->appointmentId) {
            $queryBuilder->andWhere('mh.appointmentId = :appointmentId')
                ->setParameter('appointmentId', $searchRequest->appointmentId, "uuid");
        }

        if ($searchRequest->category) {
            $queryBuilder->andWhere('mh.category = :category')
                ->setParameter('category', strtoupper($searchRequest->category));
        }

        if ($searchRequest->title) {
            $queryBuilder->andWhere('mh.title LIKE :title')
                ->setParameter('title', '%' . $searchRequest->title . '%');
        }

        if ($searchRequest->insuranceNumber) {
            $queryBuilder->andWhere('mh.insuranceNumber = :insuranceNumber')
                ->setParameter('insuranceNumber', $searchRequest->insuranceNumber);
        }

        if ($searchRequest->isPrivate !== null) {
            $queryBuilder->andWhere('mh.isPrivate = :isPrivate')
                ->setParameter('isPrivate', $searchRequest->isPrivate);
        }

        if ($searchRequest->minCost !== null) {
            $queryBuilder->andWhere('mh.cost >= :minCost')
                ->setParameter('minCost', $searchRequest->minCost);
        }

        if ($searchRequest->maxCost !== null) {
            $queryBuilder->andWhere('mh.cost <= :maxCost')
                ->setParameter('maxCost', $searchRequest->maxCost);
        }

        if ($searchRequest->dateFrom) {
            $dateFrom = $this->safeDate($searchRequest->dateFrom);
            if ($dateFrom) {
                $queryBuilder->andWhere('mh.date >= :dateFrom')
                    ->setParameter('dateFrom', $dateFrom);
            }
        }

        if ($searchRequest->dateTo) {
            $dateTo = $this->safeDate($searchRequest->dateTo);
            if ($dateTo) {
                $queryBuilder->andWhere('mh.date <= :dateTo')
                    ->setParameter('dateTo', $dateTo);
            }
        }

        if ($searchRequest->search) {
            $queryBuilder->andWhere('mh.title LIKE :search OR mh.description LIKE :search OR mh.diagnosis LIKE :search OR mh.treatment LIKE :search')
                ->setParameter('search', '%' . $searchRequest->search . '%');
        }
    }

    private function safeDate(string $value): ?\DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function createMedicalHistoryNotifications(MedicalHistory $history, string $event): void
    {
        $date = $history->getDate()?->format('d-m-Y') ?? '';
        $payload = [
            'medicalHistoryId' => $history->getId()?->toRfc4122(),
            'patientId' => $history->getPatientId()?->toRfc4122(),
            'doctorId' => $history->getDoctorId()?->toRfc4122(),
            'appointmentId' => $history->getAppointmentId()?->toRfc4122(),
            'category' => $history->getCategory()?->value,
        ];

        $titlePatient = $event === 'CREATED' ? 'Nouvel historique médical' : 'Historique médical mis à jour';
        $messagePatient = $event === 'CREATED'
            ? "Un nouvel historique médical a été ajouté le {$date}."
            : "Votre historique médical a été mis à jour le {$date}.";

        if ($history->getPatientId()) {
            $this->createNotification($history->getPatientId(), $titlePatient, $messagePatient, $payload, NotificationPriority::NORMAL);
        }

        if ($history->getDoctorId()) {
            $doctor = $this->doctorRepository->find($history->getDoctorId());
            if ($doctor && $doctor->getUserId()) {
                $titleDoctor = $event === 'CREATED' ? 'Historique médical ajouté' : 'Historique médical modifié';
                $messageDoctor = $event === 'CREATED'
                    ? "Un historique médical a été ajouté pour le patient."
                    : "Un historique médical a été mis à jour pour le patient.";
                $this->createNotification($doctor->getUserId(), $titleDoctor, $messageDoctor, $payload, NotificationPriority::LOW);
            }
        }
    }

    private function createNotification(Uuid $userId, string $title, string $message, array $data, NotificationPriority $priority): void
    {
        $notification = new Notification();
        $notification->setUserId($userId)
            ->setType(NotificationType::ALERT)
            ->setTitle($title)
            ->setMessage($message)
            ->setData($data)
            ->setPriority($priority)
            ->setIsRead(false);

        $this->entityHelper->save($notification);
    }
}
