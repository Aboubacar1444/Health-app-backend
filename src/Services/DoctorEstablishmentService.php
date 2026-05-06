<?php

namespace App\Services;

use App\Entity\DoctorEstablishment;
use App\Entity\Notification;
use App\Repository\DoctorEstablishmentRepository;
use App\Repository\DoctorRepository;
use App\Repository\UserRepository;
use App\Utils\NotificationPriority;
use App\Utils\NotificationType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctorEstablishmentService
{
    public function __construct(
        private readonly DoctorEstablishmentRepository $doctorEstablishmentRepository,
        private readonly DoctorRepository $doctorRepository,
        private readonly UserRepository $userRepository,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
    ) {}

    public function addDoctorEstablishment(array $data): JsonResponse
    {
        if (!$data || empty($data)) {
            return $this->responsesService->errorResponse("Données invalides");
        }

        $doctorEstablishment = $this->serializer->deserialize(json_encode($data), DoctorEstablishment::class, 'json');
        $doctorEstablishment->setIsPrimary(true);
        $doctorEstablishment = $this->entityHelper->save($doctorEstablishment);
        $this->createDoctorProfileCompletedNotification($doctorEstablishment);

        $body = json_decode($this->serializer->serialize($doctorEstablishment, 'json', ["groups" => "doctor_establishment"]), true);
        return $this->responsesService->successResponse($body, "Relation médecin-établissement créée");
    }

    public function getDoctorEstablishments(string $doctorId): JsonResponse
    {
        $relations = $this->doctorEstablishmentRepository->findBy(['doctorId' => $doctorId]);
        $body = json_decode($this->serializer->serialize($relations, 'json', ["groups" => "doctor_establishment"]), true);
        return $this->responsesService->successResponse($body, "Établissements du médecin");
    }

  

    public function updateDoctorEstablishment(string $id, array $data): JsonResponse
    {
        $relation = $this->doctorEstablishmentRepository->find($id);
        if (!$relation) {
            return $this->responsesService->errorResponse("Relation introuvable");
        }

        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($relation, $setter)) {
                $relation->$setter($value);
            }
        }

        $relation = $this->entityHelper->update($relation);
        $body = json_decode($this->serializer->serialize($relation, 'json', ["groups" => "doctor_establishment"]), true);
        return $this->responsesService->successResponse($body, "Relation mise à jour");
    }

    public function updateDoctorEstablishmentByDoctorId(string $id, array $data): JsonResponse
    {
        $relation = $this->doctorEstablishmentRepository->findOneBy(['doctorId'=>Uuid::fromString($id)]);
        if (!$relation) {
            return $this->responsesService->errorResponse("Relation introuvable");
        }

        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($relation, $setter)) {
                $relation->$setter($value);
            }
        }

        $relation = $this->entityHelper->update($relation);
        $body = json_decode($this->serializer->serialize($relation, 'json', ["groups" => "doctor_establishment"]), true);
        return $this->responsesService->successResponse($body, "Relation mise à jour");
    }


    public function removeDoctorEstablishment(string $id): JsonResponse
    {
        $relation = $this->doctorEstablishmentRepository->find($id);
        if (!$relation) {
            return $this->responsesService->errorResponse("Relation introuvable");
        }

        $this->entityHelper->remove($relation);
        return $this->responsesService->successResponse([], "Relation supprimée");
    }

    private function createDoctorProfileCompletedNotification(DoctorEstablishment $relation): void
    {
        $doctorId = $relation->getDoctorId();
        if (!$doctorId) {
            return;
        }

        $doctor = $this->doctorRepository->find($doctorId);
        $doctorUser = $doctor?->getUser();
        $doctorName = $doctorUser?->getFullName();

        $admins = $this->userRepository->findBy([
            'userJob' => ['ADMIN', 'GESTION', 'BACKOFFICE'],
        ]);

        if ($admins === []) {
            return;
        }

        $notifications = [];
        foreach ($admins as $admin) {
            if (!$admin->getId()) {
                continue;
            }
            $notification = new Notification();
            $notification->setUserId($admin->getId())
                ->setType(NotificationType::SYSTEM)
                ->setTitle('Profil médecin complété')
                ->setMessage($doctorName
                    ? "Le profil du médecin {$doctorName} a été complété."
                    : "Le profil d'un médecin a été complété.")
                ->setData([
                    'doctorId' => $doctorId?->toRfc4122(),
                    'establishmentId' => $relation->getEstablishmentId()?->toRfc4122(),
                ])
                ->setPriority(NotificationPriority::NORMAL)
                ->setIsRead(false);

            $notifications[] = $notification;
        }

        if ($notifications !== []) {
            $this->entityHelper->saveMultiple($notifications);
        }
    }
}
