<?php

namespace App\Controller;

use App\Entity\Speciality;
use App\Repository\SpecialityRepository;
use App\Services\ResponsesService;
use App\Services\EntityHelperService;
use App\Services\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/speciality')]
class SpecialityController extends AbstractController
{
    public function __construct(
        private readonly SpecialityRepository $specialityRepository,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
        private readonly PaginationService $paginationService,
    ) {}

    #[Route('', name: 'get_all_specialities', methods: ['GET'])]
    public function getAllSpecialities(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        
        $queryBuilder = $this->specialityRepository->createQueryBuilder('s')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.sortOrder', 'ASC');
            
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ['speciality']);
    }

    #[Route('/{id}', name: 'get_speciality_by_id', methods: ['GET'])]
    public function getSpecialityById(string $id): JsonResponse
    {
        $speciality = $this->specialityRepository->find($id);
        
        if (!$speciality) {
            return $this->responsesService->errorResponse("Spécialité introuvable");
        }
        
        $body = json_decode($this->serializer->serialize($speciality, 'json', ["groups" => "speciality"]), true);
        return $this->responsesService->successResponse($body, "Spécialité trouvée");
    }

    #[Route('', name: 'create_speciality', methods: ['POST'])]
    public function createSpeciality(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || empty($data)) {
            return $this->responsesService->errorResponse("Données invalides");
        }

        $speciality = $this->serializer->deserialize(json_encode($data), Speciality::class, 'json');
        $speciality = $this->entityHelper->save($speciality);

        $body = json_decode($this->serializer->serialize($speciality, 'json', ["groups" => "speciality"]), true);
        return $this->responsesService->successResponse($body, "Spécialité créée avec succès");
    }

    #[Route('/{id}', name: 'update_speciality', methods: ['PUT'])]
    public function updateSpeciality(string $id, Request $request): JsonResponse
    {
        $speciality = $this->specialityRepository->find($id);
        if (!$speciality) {
            return $this->responsesService->errorResponse("Spécialité introuvable");
        }

        $data = json_decode($request->getContent(), true);
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($speciality, $setter)) {
                $speciality->$setter($value);
            }
        }

        $speciality = $this->entityHelper->update($speciality);
        $body = json_decode($this->serializer->serialize($speciality, 'json', ["groups" => "speciality"]), true);
        return $this->responsesService->successResponse($body, "Spécialité mise à jour");
    }

    #[Route('/{id}', name: 'delete_speciality', methods: ['DELETE'])]
    public function deleteSpeciality(string $id): JsonResponse
    {
        $speciality = $this->specialityRepository->find($id);
        if (!$speciality) {
            return $this->responsesService->errorResponse("Spécialité introuvable");
        }

        $this->entityHelper->remove($speciality);
        return $this->responsesService->successResponse([], "Spécialité supprimée");
    }
}