<?php

namespace App\Controller;

use App\Services\EstablishmentService;
use App\Request\EstablishmentSearchRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/establishments')]
final class EstablishmentController extends AbstractController
{
    public function __construct(private readonly EstablishmentService $establishmentService) {}

    #[Route('', name: 'app_establishment_create', methods: "POST")]
    public function addEstablishment(Request $request): JsonResponse
    {
        if ($request->files->get('file')) {
            $data = json_decode($request->request->get('data'), true);

            return $this->establishmentService->addEstablishment(
                $data,
                $request->files->get('file')
            );
        }
        $data = json_decode($request->getContent(), true);

        return $this->establishmentService->addEstablishment($data);

    }
    

    #[Route('/search', name: 'app_establishment_search', methods: "POST")]
    public function searchEstablishments(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $searchRequest = new EstablishmentSearchRequest($data);
        return $this->establishmentService->getAllEstablishments($searchRequest);
    }

    #[Route('/active', name: 'app_establishment_active', methods: "GET")]
    public function getActiveEstablishments(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        return $this->establishmentService->getActiveEstablishments($page, $limit);
    }

    #[Route('/city/{city}', name: 'app_establishment_by_city', methods: "GET")]
    public function getEstablishmentsByCity(string $city, Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        return $this->establishmentService->getEstablishmentsByCity($city, $page, $limit);
    }

    #[Route('/{id}', name: 'app_establishment_show', methods: "GET")]
    public function getEstablishmentById(string $id): JsonResponse
    {
        return $this->establishmentService->getEstablishmentById($id);
    }

    #[Route('/{id}', name: 'app_establishment_update', methods: "PUT")]
    public function updateEstablishment(string $id, Request $request): JsonResponse
    {
        if ($request->files->get('file')) {
            $data = json_decode($request->request->get('data'), true);

            return $this->establishmentService->updateEstablishment(
                $id,
                $data,
                $request->files->get('file')
            );
        }
        $data = json_decode($request->getContent(), true);
        return $this->establishmentService->updateEstablishment($id, $data);
    }

    #[Route('/{id}', name: 'app_establishment_delete', methods: "DELETE")]
    public function removeEstablishment(string $id): JsonResponse
    {
        return $this->establishmentService->removeEstablishment($id);
    }
}