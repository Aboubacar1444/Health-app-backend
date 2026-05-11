<?php

namespace App\Controller;

use App\Services\CommuneService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/communes')]
class CommuneController extends AbstractController
{
    public function __construct(
        private readonly CommuneService $communeService
    ) {}

    #[Route('', name: 'app_commune_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->communeService->addComune($data);
    }

    #[Route('', name: 'app_commune_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        return $this->communeService->getAllCommunes($page, $limit);
    }
    #[Route('/{id}', name: 'app_commune_edit', methods: ['PUT'])]
    public function updateCommune(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->communeService->updateCommune($id, $data);
    }
    

    #[Route('/{id}', name: 'app_commune_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        return $this->communeService->getCommuneById($id);
    }

    #[Route('/{id}', name: 'app_commune_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        return $this->communeService->deleteCommune($id);
    }

    #[Route('/{id}/pharmacies', name: 'app_commune_pharmacies', methods: ['GET'])]
    public function getPharmacies(string $id, Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        return $this->communeService->getCommunePharmacies($id, $page, $limit);
    }
}