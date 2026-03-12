<?php

namespace App\Services;

use App\Entity\DoctorEstablishment;
use App\Repository\DoctorEstablishmentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

final class DoctorEstablishmentService
{
    public function __construct(
        private readonly DoctorEstablishmentRepository $doctorEstablishmentRepository,
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
        $doctorEstablishment = $this->entityHelper->save($doctorEstablishment);

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

    public function removeDoctorEstablishment(string $id): JsonResponse
    {
        $relation = $this->doctorEstablishmentRepository->find($id);
        if (!$relation) {
            return $this->responsesService->errorResponse("Relation introuvable");
        }

        $this->entityHelper->remove($relation);
        return $this->responsesService->successResponse([], "Relation supprimée");
    }
}