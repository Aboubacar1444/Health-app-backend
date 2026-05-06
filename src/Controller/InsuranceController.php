<?php

namespace App\Controller;

use App\Entity\Insurance;
use App\Repository\InsuranceRepository;
use App\Services\EntityHelperService;
use App\Services\PaginationService;
use App\Services\ResponsesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/insurance')]
class InsuranceController extends AbstractController
{
    public function __construct(
        private readonly InsuranceRepository $insuranceRepository,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
        private readonly PaginationService $paginationService,
    ) {}

    #[Route('', name: 'get_all_insurances', methods: ['GET'])]
    public function getAllInsurances(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $queryBuilder = $this->insuranceRepository->createQueryBuilder('i')
            ->orderBy('i.name', 'ASC');

        return $this->paginationService->paginate($queryBuilder, $page, $limit, ['insurance']);
    }

    #[Route('/{id}', name: 'get_insurance_by_id', methods: ['GET'])]
    public function getInsuranceById(string $id): JsonResponse
    {
        $insurance = $this->insuranceRepository->find($id);

        if (!$insurance) {
            return $this->responsesService->errorResponse("Assurance introuvable");
        }

        $body = json_decode($this->serializer->serialize($insurance, 'json', ["groups" => "insurance"]), true);
        return $this->responsesService->successResponse($body, "Assurance trouvée");
    }

    #[Route('', name: 'create_insurance', methods: ['POST'])]
    public function createInsurance(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data)) {
            return $this->responsesService->errorResponse("Données invalides");
        }

        $insurance = $this->serializer->deserialize(json_encode($data), Insurance::class, 'json');
        $insurance = $this->entityHelper->save($insurance);

        $body = json_decode($this->serializer->serialize($insurance, 'json', ["groups" => "insurance"]), true);
        return $this->responsesService->successResponse($body, "Assurance créée avec succès");
    }

    #[Route('/{id}', name: 'update_insurance', methods: ['PUT'])]
    public function updateInsurance(string $id, Request $request): JsonResponse
    {
        $insurance = $this->insuranceRepository->find($id);
        if (!$insurance) {
            return $this->responsesService->errorResponse("Assurance introuvable");
        }

        $data = json_decode($request->getContent(), true);
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($insurance, $setter)) {
                $insurance->$setter($value);
            }
        }

        $insurance = $this->entityHelper->update($insurance);
        $body = json_decode($this->serializer->serialize($insurance, 'json', ["groups" => "insurance"]), true);
        return $this->responsesService->successResponse($body, "Assurance mise à jour");
    }

    #[Route('/{id}', name: 'delete_insurance', methods: ['DELETE'])]
    public function deleteInsurance(string $id): JsonResponse
    {
        $insurance = $this->insuranceRepository->find($id);
        if (!$insurance) {
            return $this->responsesService->errorResponse("Assurance introuvable");
        }

        $this->entityHelper->remove($insurance);
        return $this->responsesService->successResponse([], "Assurance supprimée");
    }
}
