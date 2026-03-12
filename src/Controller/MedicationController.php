<?php

namespace App\Controller;

use App\Request\MedicationSearchRequest;
use App\Services\MedicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/medications')]
class MedicationController extends AbstractController
{
    public function __construct(
        private readonly MedicationService $medicationService
    ) {}

    #[Route('', name: 'app_medication_create_from_file', methods: ['POST'])]
    public function createFromFile(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        return $this->medicationService->createMedicationsFromExcel($file);
    }

    #[Route('', name: 'app_medication_update_from_file', methods: ['PUT'])]
    public function updateInsuranceStatusFromFile(Request $request): JsonResponse
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        ini_set('upload_max_filesize', '512M');
        ini_set('post_max_size', '512M');
        $file = $request->files->get('file');
        
        return $this->medicationService->extractAndUpdateMedicationsCSV($file);
    }

    //Update medications from BDPM data
    #[Route('/update', name: 'app_medication_update_from_bdpm', methods: ['POST'])]
    public function updateFromBdpm(Request $request): JsonResponse
    {
        set_time_limit(0);
        ini_set('memory_limit', "10000M");
        $this->medicationService->enrichMedicationsFromBdpm();
        return new JsonResponse(
            [   'status' => 1, 
                'message' => 'Medications updated successfully from BDPM data', 
                "body" => [] 
            ], JsonResponse::HTTP_OK);
    }
    #[Route('/search', name: 'app_medication_search', methods: ['POST'])]
    public function search(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $searchRequest = new MedicationSearchRequest($data);
        return $this->medicationService->getAllMedications($searchRequest);
    }

    #[Route('/category/{category}', name: 'app_medication_by_category', methods: ['GET'])]
    public function getByCategory(string $category, Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        return $this->medicationService->getMedicationsByCategory($category, $page, $limit);
    }

    #[Route('/prescription', name: 'app_medication_prescription', methods: ['GET'])]
    public function getPrescription(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        return $this->medicationService->getPrescriptionMedications($page, $limit);
    }

    #[Route('/otc', name: 'app_medication_otc', methods: ['GET'])]
    public function getOtc(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        return $this->medicationService->getOtcMedications($page, $limit);
    }

    #[Route('/{id}', name: 'app_medication_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        return $this->medicationService->getMedicationById($id);
    }
}