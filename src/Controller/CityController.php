<?php

namespace App\Controller;

use App\Entity\City;
use App\Repository\CityRepository;
use App\Services\ResponsesService;
use App\Services\EntityHelperService;
use App\Services\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/city')]
class CityController extends AbstractController
{
    public function __construct(
        private readonly CityRepository $cityRepository,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
        private readonly PaginationService $paginationService,
    ) {}

    #[Route('', name: 'get_all_cities', methods: ['GET'])]
    public function getAllCities(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        
        $queryBuilder = $this->cityRepository->createQueryBuilder('c')
            ->where('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC');
            
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ['city']);
    }

    #[Route('/{id}', name: 'get_city_by_id', methods: ['GET'])]
    public function getCityById(string $id): JsonResponse
    {
        $city = $this->cityRepository->find($id);
        
        if (!$city) {
            return $this->responsesService->errorResponse("Ville introuvable");
        }
        
        $body = json_decode($this->serializer->serialize($city, 'json', ["groups" => "city"]), true);
        return $this->responsesService->successResponse($body, "Ville trouvée");
    }

    #[Route('', name: 'create_city', methods: ['POST'])]
    public function createCity(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || empty($data)) {
            return $this->responsesService->errorResponse("Données invalides");
        }

        $city = $this->serializer->deserialize(json_encode($data), City::class, 'json');
        $city = $this->entityHelper->save($city);

        $body = json_decode($this->serializer->serialize($city, 'json', ["groups" => "city"]), true);
        return $this->responsesService->successResponse($body, "Ville créée avec succès");
    }

    #[Route('/{id}', name: 'update_city', methods: ['PUT'])]
    public function updateCity(string $id, Request $request): JsonResponse
    {
        $city = $this->cityRepository->find($id);
        if (!$city) {
            return $this->responsesService->errorResponse("Ville introuvable");
        }

        $data = json_decode($request->getContent(), true);
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($city, $setter)) {
                $city->$setter($value);
            }
        }

        $city = $this->entityHelper->update($city);
        $body = json_decode($this->serializer->serialize($city, 'json', ["groups" => "city"]), true);
        return $this->responsesService->successResponse($body, "Ville mise à jour");
    }

    #[Route('/{id}', name: 'delete_city', methods: ['DELETE'])]
    public function deleteCity(string $id): JsonResponse
    {
        $city = $this->cityRepository->find($id);
        if (!$city) {
            return $this->responsesService->errorResponse("Ville introuvable");
        }

        $this->entityHelper->remove($city);
        return $this->responsesService->successResponse([], "Ville supprimée");
    }
}