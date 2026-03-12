<?php

namespace App\Controller;

use App\Request\PharmacySearchRequest;
use App\Services\PharmacyService;
use App\Services\PharmacyDutyScheduleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pharmacies')]
class PharmacyController extends AbstractController
{
    public function __construct(
        private readonly PharmacyService $pharmacyService,
        private readonly PharmacyDutyScheduleService $dutyScheduleService
    ) {}

    #[Route('', name: 'app_pharmacy_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->pharmacyService->addPharmacy($data);
    }

    #[Route('/search', name: 'app_pharmacy_search', methods: ['POST'])]
    public function search(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $searchRequest = new PharmacySearchRequest($data);
        return $this->pharmacyService->getAllPharmacies($searchRequest);
    }

    #[Route('/on-duty', name: 'app_pharmacy_on_duty', methods: ['GET'])]
    public function getOnDuty(Request $request): JsonResponse
    {
        $commune = $request->query->get('commune');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        return $this->dutyScheduleService->getOnDutyPharmacies($commune, $page, $limit);
    }

    #[Route('/24h', name: 'app_pharmacy_open_24h', methods: ['GET'])]
    public function getOpen24h(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        return $this->pharmacyService->getOpen24hPharmacies($page, $limit);
    }

    #[Route('/city/{city}', name: 'app_pharmacy_by_city', methods: ['GET'])]
    public function getByCity(string $city, Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        return $this->pharmacyService->getPharmaciesByCity($city, $page, $limit);
    }

    #[Route('/commune/{commune}', name: 'app_pharmacy_by_commune', methods: ['GET'])]
    public function getByCommune(string $commune, Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        return $this->pharmacyService->getPharmaciesByCommune($commune, $page, $limit);
    }

    #[Route('/{id}', name: 'app_pharmacy_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        return $this->pharmacyService->getPharmacyById($id);
    }

    #[Route('/{id}', name: 'app_pharmacy_update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->pharmacyService->updatePharmacy($id, $data);
    }

    #[Route('/{id}', name: 'app_pharmacy_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        return $this->pharmacyService->removePharmacy($id);
    }

    #[Route('/{id}/duty-schedule', name: 'app_pharmacy_create_duty_schedule', methods: ['POST'])]
    public function createDutySchedule(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->dutyScheduleService->createDutySchedule($id, $data);
    }
}