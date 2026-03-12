<?php

namespace App\Controller;

use App\Entity\Emergency;
use App\Repository\EmergencyRepository;
use App\Services\ResponsesService;
use App\Services\EntityHelperService;
use App\Services\PaginationService;
use App\Services\EmergencyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/emergency')]
class EmergencyController extends AbstractController
{
    public function __construct(
        private readonly EmergencyService $emergencyService,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly PaginationService $paginationService,
    ) {}

    #[Route('', name: 'get_all_emergencies', methods: ['GET'])]
    public function getAllEmergencies(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $queryBuilder = $this->emergencyService->getQueryBuilder();

        return $this->paginationService->paginate($queryBuilder, $page, $limit, ['emergency']);
    }

    #[Route('/{id}', name: 'get_emergency_by_id', methods: ['GET'])]
    public function getEmergencyById(string $id): JsonResponse
    {
        $emergency = $this->emergencyService->find($id);
        if (!$emergency) {
            return $this->responsesService->errorResponse("Emergency introuvable");
        }

        $body = json_decode($this->serializer->serialize($emergency, 'json', ["groups" => "emergency"]), true);
        return $this->responsesService->successResponse($body, "Emergency trouvé");
    }

    #[Route('', name: 'create_emergency', methods: ['POST'])]
    public function createEmergency(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || empty($data)) {
            return $this->responsesService->errorResponse("Données invalides");
        }

        /** @var Emergency $emergency */
        $emergency = $this->serializer->deserialize(json_encode($data), Emergency::class, 'json');
        $emergency = $this->emergencyService->save($emergency);

        $body = json_decode($this->serializer->serialize($emergency, 'json', ["groups" => "emergency"]), true);
        return $this->responsesService->successResponse($body, "Emergency créé avec succès");
    }

    #[Route('/{id}', name: 'update_emergency', methods: ['PUT'])]
    public function updateEmergency(string $id, Request $request): JsonResponse
    {
        $emergency = $this->emergencyService->find($id);
        if (!$emergency) {
            return $this->responsesService->errorResponse("Emergency introuvable");
        }

        $data = json_decode($request->getContent(), true);
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($emergency, $setter)) {
                $emergency->$setter($value);
            }
        }

        $emergency = $this->emergencyService->update($emergency);
        $body = json_decode($this->serializer->serialize($emergency, 'json', ["groups" => "emergency"]), true);
        return $this->responsesService->successResponse($body, "Emergency mis à jour");
    }

    #[Route('/{id}', name: 'delete_emergency', methods: ['DELETE'])]
    public function deleteEmergency(string $id): JsonResponse
    {
        $emergency = $this->emergencyService->find($id);
        if (!$emergency) {
            return $this->responsesService->errorResponse("Emergency introuvable");
        }

        $this->emergencyService->remove($emergency);
        return $this->responsesService->successResponse([], "Emergency supprimé");
    }
}
