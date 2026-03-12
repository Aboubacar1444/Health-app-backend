<?php

namespace App\Services;

use App\Entity\Appointment;
use App\Entity\Notification;
use App\Repository\AppointmentRepository;
use App\Repository\DoctorRepository;
use App\Repository\UserRepository;
use App\Request\AppointmentSearchRequest;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use App\Utils\NotificationPriority;
use App\Utils\NotificationType;

final class AppointmentService
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly DoctorRepository $doctorRepository,
        private readonly UserRepository $userRepository,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
    ) {}

    public function addAppointment(array $data): JsonResponse
    {
        if (!$data || empty($data)) {
            return $this->responsesService->errorResponse("Données invalides");
        }

        $appointment = $this->serializer->deserialize(json_encode($data), Appointment::class, 'json');
        $appointment = $this->entityHelper->save($appointment);
        $this->createAppointmentNotifications($appointment, 'CREATED');

        $body = json_decode($this->serializer->serialize($appointment, 'json', ["groups" => "appointment"]), true);
        return $this->responsesService->successResponse($body, "Rendez-vous créé avec succès");
    }

    public function getAllAppointments(?AppointmentSearchRequest $searchRequest = null): JsonResponse
    {
        $queryBuilder = $this->appointmentRepository->createQueryBuilder('a');
        
        if ($searchRequest) {
            $this->applyAppointmentFilters($queryBuilder, $searchRequest);
            return $this->paginateAppointmentsWithRelations($queryBuilder, $searchRequest->page, $searchRequest->limit);
        }
        
        return $this->paginateAppointmentsWithRelations($queryBuilder, 1, 10);
    }

    private function applyAppointmentFilters($queryBuilder, AppointmentSearchRequest $searchRequest): void
    {
        if ($searchRequest->patientId) {
            $queryBuilder->andWhere('a.patientId = :patientId')
                ->setParameter('patientId', $searchRequest->patientId);
        }
        
        if ($searchRequest->doctorId) {
            $queryBuilder->andWhere('a.doctorId = :doctorId')
                ->setParameter('doctorId', $searchRequest->doctorId);
        }
        
        if ($searchRequest->establishmentId) {
            $queryBuilder->andWhere('a.establishmentId = :establishmentId')
                ->setParameter('establishmentId', $searchRequest->establishmentId);
        }
        
        if ($searchRequest->status) {
            $queryBuilder->andWhere('a.status = :status')
                ->setParameter('status', $searchRequest->status);
        }
        
        if ($searchRequest->priority) {
            $queryBuilder->andWhere('a.priority = :priority')
                ->setParameter('priority', $searchRequest->priority);
        }
        
        if ($searchRequest->isEmergency !== null) {
            $queryBuilder->andWhere('a.isEmergency = :isEmergency')
                ->setParameter('isEmergency', $searchRequest->isEmergency);
        }
        
        if ($searchRequest->dateFrom) {
            $queryBuilder->andWhere('a.appointmentDate >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($searchRequest->dateFrom));
        }
        
        if ($searchRequest->dateTo) {
            $queryBuilder->andWhere('a.appointmentDate <= :dateTo')
                ->setParameter('dateTo', new \DateTime($searchRequest->dateTo));
        }
        
        if ($searchRequest->search) {
            $queryBuilder->andWhere('a.reason LIKE :search OR a.patientSymptoms LIKE :search')
                ->setParameter('search', '%' . $searchRequest->search . '%');
        }
    }

    public function getAppointmentById(string $id): JsonResponse
    {
        $appointment = $this->appointmentRepository->find($id);
        
        if (!$appointment) {
            return $this->responsesService->errorResponse("Rendez-vous introuvable");
        }

        $this->hydrateAppointmentRelations([$appointment]);
        
        $body = json_decode($this->serializer->serialize($appointment, 'json', ["groups" => "appointment"]), true);
        
        if ($body === null) {
            return $this->responsesService->errorResponse("Erreur de sérialisation");
        }
        
        return $this->responsesService->successResponse($body, "Rendez-vous trouvé");
    }

    public function updateAppointment(string $id, array $data): JsonResponse
    {
        $appointment = $this->appointmentRepository->find($id);
        if (!$appointment) {
            return $this->responsesService->errorResponse("Rendez-vous introuvable");
        }

        $previousStatus = $appointment->getStatus();

        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($appointment, $setter)) {
                $appointment->$setter($value);
            }
        }

        $appointment = $this->entityHelper->update($appointment);
        if (isset($data['status']) && $previousStatus !== $appointment->getStatus()) {
            $this->createAppointmentNotifications($appointment, 'STATUS_UPDATED', $previousStatus);
        }
        $body = json_decode($this->serializer->serialize($appointment, 'json', ["groups" => "appointment"]), true);
        return $this->responsesService->successResponse($body, "Rendez-vous mis à jour");
    }

    public function cancelAppointment(string $id, string $cancelledBy, string $reason): JsonResponse
    {
        $appointment = $this->appointmentRepository->find($id);
        if (!$appointment) {
            return $this->responsesService->errorResponse("Rendez-vous introuvable");
        }

        $appointment->setStatus('CANCELLED');
        $appointment->setCancelledBy(Uuid::fromString($cancelledBy));
        $appointment->setCancelledAt(new \DateTimeImmutable());
        $appointment->setCancellationReason($reason);

        $appointment = $this->entityHelper->update($appointment);
        $this->createAppointmentNotifications($appointment, 'CANCELLED');
        $body = json_decode($this->serializer->serialize($appointment, 'json', ["groups" => "appointment"]), true);
        return $this->responsesService->successResponse($body, "Rendez-vous annulé");
    }

    public function confirmAppointment(string $id): JsonResponse
    {
        $appointment = $this->appointmentRepository->find($id);
        if (!$appointment) {
            return $this->responsesService->errorResponse("Rendez-vous introuvable");
        }

        $appointment->setStatus('CONFIRMED');
        $appointment = $this->entityHelper->update($appointment);
        $this->createAppointmentNotifications($appointment, 'CONFIRMED');
        $body = json_decode($this->serializer->serialize($appointment, 'json', ["groups" => "appointment"]), true);
        return $this->responsesService->successResponse($body, "Rendez-vous confirmé");
    }

    public function completeAppointment(string $id): JsonResponse
    {
        $appointment = $this->appointmentRepository->find($id);
        if (!$appointment) {
            return $this->responsesService->errorResponse("Rendez-vous introuvable");
        }

        $appointment->setStatus('COMPLETED');
        $appointment = $this->entityHelper->update($appointment);
        $this->createAppointmentNotifications($appointment, 'COMPLETED');
        $body = json_decode($this->serializer->serialize($appointment, 'json', ["groups" => "appointment"]), true);
        return $this->responsesService->successResponse($body, "Rendez-vous terminé");
    }

    public function getPatientAppointments(string $patientId, int $page = 1, int $limit = 10): JsonResponse
    {
        $queryBuilder = $this->appointmentRepository->createQueryBuilder('a')
            ->where('a.patientId = :patientId')
            ->setParameter('patientId', $patientId, "uuid")
            ->orderBy('a.appointmentDate', 'DESC');
        return $this->paginateAppointmentsWithRelations($queryBuilder, $page, $limit);
    }

    public function getDoctorAppointments(string $doctorId, int $page = 1, int $limit = 10): JsonResponse
    {
        $queryBuilder = $this->appointmentRepository->createQueryBuilder('a')
            ->where('a.doctorId = :doctorId')
            ->setParameter('doctorId', $doctorId, "uuid")
            ->orderBy('a.appointmentDate', 'ASC');
        return $this->paginateAppointmentsWithRelations($queryBuilder, $page, $limit);
    }

    private function paginateAppointmentsWithRelations(QueryBuilder $queryBuilder, int $page = 1, int $limit = 10): JsonResponse
    {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));

        $totalQuery = clone $queryBuilder;
        $rootAliases = $totalQuery->getRootAliases();
        $rootAlias = $rootAliases[0];
        $total = $totalQuery->select("COUNT(DISTINCT {$rootAlias}.id)")->getQuery()->getSingleScalarResult();

        $offset = ($page - 1) * $limit;
        $appointments = $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $this->hydrateAppointmentRelations($appointments);

        $data = json_decode($this->serializer->serialize($appointments, 'json', ["groups" => ["appointment"]]), true);

        $response = [
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => (int) $total,
                'total_pages' => (int) ceil($total / $limit),
                'has_next' => $page < ceil($total / $limit),
                'has_prev' => $page > 1
            ],
            'data' => $data,
        ];

        return $this->responsesService->successResponse($response, "Données paginées");
    }

    private function hydrateAppointmentRelations(array $appointments): void
    {
        foreach ($appointments as $appointment) {
            if (!$appointment instanceof Appointment) {
                continue;
            }

            $doctor = $this->doctorRepository->find($appointment->getDoctorId());
            if ($doctor) {
                $doctorUser = $doctor->getUser() ?? $this->userRepository->find($doctor->getUserId());
                $appointment->setDoctor([
                    'id' => $doctor->getId(),
                    'fullName' => $doctorUser?->getFullName(),
                    'profileImage' => $doctorUser?->getProfileImage(),
                    'speciality' => $doctor->getSpeciality(),
                ]);
            }

            $patient = $this->userRepository->find($appointment->getPatientId());
            if ($patient) {
                $appointment->setPatient([
                    'id' => $patient->getId(),
                    'fullName' => $patient->getFullName(),
                    'profileImage' => $patient->getProfileImage(),
                    'phone' => $patient->getPhone(),
                    'email' => $patient->getEmail(),
                ]);
            }
        }
    }

    private function createAppointmentNotifications(Appointment $appointment, string $event, ?string $previousStatus = null): void
    {
        $date = $appointment->getAppointmentDate()?->format('d-m-Y') ?? '';
        $time = $appointment->getAppointmentTime()?->format('H:i') ?? '';
        $doctor = $this->doctorRepository->find($appointment->getDoctorId());
        $doctorUser = $doctor ? ($doctor->getUser() ?? $this->userRepository->find($doctor->getUserId())) : null;
        $patient = $this->userRepository->find($appointment->getPatientId());

        $payload = [
            'appointmentId' => $appointment->getId()?->toRfc4122(),
            'status' => $appointment->getStatus(),
            'previousStatus' => $previousStatus,
            'doctorId' => $appointment->getDoctorId()?->toRfc4122(),
            'patientId' => $appointment->getPatientId()?->toRfc4122(),
        ];

        switch ($event) {
            case 'CREATED':
                $titlePatient = 'Rendez-vous créé';
                $messagePatient = "Votre rendez-vous est prévu le {$date} à {$time}.";
                $titleDoctor = 'Nouveau rendez-vous';
                $messageDoctor = "Nouveau rendez-vous le {$date} à {$time} avec {$patient?->getFullName()}.";
                $priority = NotificationPriority::HIGH;
                break;
            case 'CONFIRMED':
                $titlePatient = 'Rendez-vous confirmé';
                $messagePatient = "Votre rendez-vous du {$date} à {$time} a été confirmé avec Dr. {$doctorUser?->getFullName()}.";
                $titleDoctor = 'Rendez-vous confirmé';
                $messageDoctor = "Le rendez-vous du {$date} à {$time} a été confirmé pour {$patient?->getFullName()}.";
                $priority = NotificationPriority::HIGH;
                break;
            case 'COMPLETED':
                $titlePatient = 'Rendez-vous terminé';
                $messagePatient = "Votre rendez-vous du {$date} à {$time} est terminé avec Dr. {$doctorUser?->getFullName()}.";
                $titleDoctor = 'Rendez-vous terminé';
                $messageDoctor = "Le rendez-vous du {$date} à {$time} est terminé pour {$patient?->getFullName()}.";
                $priority = NotificationPriority::NORMAL;
                break;
            case 'CANCELLED':
                $titlePatient = 'Rendez-vous annulé';
                $messagePatient = "Votre rendez-vous du {$date} à {$time} a été annulé avec Dr. {$doctorUser?->getFullName()}.";
                $titleDoctor = 'Rendez-vous annulé';
                $messageDoctor = "Le rendez-vous du {$date} à {$time} a été annulé pour {$patient?->getFullName()}.";
                $priority = NotificationPriority::HIGH;
                break;
            case 'STATUS_UPDATED':
            default:
                $titlePatient = 'Statut du rendez-vous mis à jour';
                $messagePatient = "Le statut de votre rendez-vous avec Dr. {$doctorUser?->getFullName()} est maintenant {$appointment->getStatus()}.";
                $titleDoctor = 'Statut du rendez-vous mis à jour';
                $messageDoctor = "Le statut du rendez-vous de {$patient?->getFullName()} est maintenant {$appointment->getStatus()}.";
                $priority = NotificationPriority::NORMAL;
                break;
        }


        if ($doctor && $doctor->getUserId()) {
            $this->createNotification($doctor->getUserId(), $titleDoctor, $messageDoctor, $payload, $priority);
        }
        sleep(2); // Petite pause pour éviter les conflits de notifications simultanées

        if ($appointment->getPatientId()) {
            $this->createNotification($appointment->getPatientId(), $titlePatient, $messagePatient, $payload, $priority);
        }
    }

    private function createNotification(Uuid $userId, string $title, string $message, array $data, NotificationPriority $priority): void
    {
        $notification = new Notification();
        $notification->setUserId($userId)
            ->setType(NotificationType::APPOINTMENT)
            ->setTitle($title)
            ->setMessage($message)
            ->setData($data)
            ->setPriority($priority)
            ->setIsRead(false);

        $this->entityHelper->save($notification);
    }
}
