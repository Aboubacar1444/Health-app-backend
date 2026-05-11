<?php

namespace App\Services;

use App\Entity\Commune;
use App\Repository\CommuneRepository;
use App\Repository\PharmacyRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

final class CommuneService
{
    public function __construct(
        private readonly CommuneRepository $communeRepository,
        private readonly PharmacyRepository $pharmacyRepository,
        private readonly ResponsesService $responsesService,
        private readonly EntityHelperService $entityHelper,
        private readonly SerializerInterface $serializer,
        private readonly PaginationService $paginationService,
    ) {}

    public function addComune(array $data): JsonResponse
    {
        if (!$data || empty($data)) {
            return $this->responsesService->errorResponse("Données invalides");
        }

        $commune = $this->serializer->deserialize(json_encode($data), Commune::class, 'json');
        $commune = $this->entityHelper->save($commune);

        $body = json_decode($this->serializer->serialize($commune, 'json', ["groups" => "commune"]), true);
        return $this->responsesService->successResponse($body, "Commune créée avec succès");
    }
    
    public function deleteCommune(string $id): JsonResponse
    {
        $commune = $this->communeRepository->find($id);
        if (!$commune) {
            return $this->responsesService->errorResponse("Commune introuvable");
        }
        $this->entityHelper->remove($commune);
        return $this->responsesService->successResponse([], "Commune supprimée avec succès");
    }

    public function getCommuneById(string $id): JsonResponse
    {
        $commune = $this->communeRepository->find($id);
        if (!$commune) {
            return $this->responsesService->errorResponse("Commune introuvable");
        }
        $body = json_decode($this->serializer->serialize($commune, 'json', ["groups" => "commune"]), true);
        return $this->responsesService->successResponse($body, "Commune trouvée");
    }

    public function getAllCommunes(int $page = 1, int $limit = 10): JsonResponse
    {
        $queryBuilder = $this->communeRepository->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC');
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ["commune"]);
    }

    public function getCommunePharmacies(string $communeId, int $page = 1, int $limit = 10): JsonResponse
    {
        $queryBuilder = $this->pharmacyRepository->createQueryBuilder('p')
            ->where('p.communeId = :communeId')
            ->andWhere('p.isActive = :active')
            ->setParameter('communeId', $communeId)
            ->setParameter('active', true);
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ["pharmacy"]);
    }

    public function updateCommune(string $id, ?array $data): JsonResponse
    {
        $commune = $this->communeRepository->find($id);
        if (!$commune) {
            return $this->responsesService->errorResponse("Commune introuvable");
        }

        $updatedCommune = $this->serializer->deserialize(json_encode($data), Commune::class, 'json', ['object_to_populate' => $commune]);
        $updatedCommune = $this->entityHelper->save($updatedCommune);

        $body = json_decode($this->serializer->serialize($updatedCommune, 'json', ["groups" => "commune"]), true);
        return $this->responsesService->successResponse($body, "Commune mise à jour avec succès");
    }
}