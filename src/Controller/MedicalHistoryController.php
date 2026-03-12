<?php

namespace App\Controller;

use App\Entity\User;
use App\Request\MedicalHistorySearchRequest;
use App\Services\MedicalHistoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/medical-history')]
class MedicalHistoryController extends AbstractController
{
    public function __construct(
        private readonly MedicalHistoryService $medicalHistoryService,
    ) {}

    // #[Route('', name: 'get_all_medical_histories', methods: ['GET'])]
    // public function getAllMedicalHistories(Request $request): JsonResponse
    // {
    //     $data = $request->query->all();
    //     $searchRequest = new MedicalHistorySearchRequest($data);
    //     return $this->medicalHistoryService->getAllMedicalHistories($searchRequest);
    // }

    #[Route('/search', name: 'search_medical_histories', methods: ['POST'])]
    public function searchMedicalHistories(#[CurrentUser()] User $user, Request $request): JsonResponse
    {
        if ($user->getUserJob() != "MEDECIN" && $user->getUserJob() != "VISITEUR" && $user->getUserJob() != "AUTRE") {
            return $this->json(['message' => 'Access denied'], 403);
        }
       
        $data = json_decode($request->getContent(), true);
        match ($user->getUserJob()) {
            "DOCTOR" => $data = ['doctorId' => $user->getId()],
            "VISITEUR", "AUTRE" => $data = ['patientId' => $user->getId()],
            default => $data = []
        };
        
        $searchRequest = new MedicalHistorySearchRequest($data);
        return $this->medicalHistoryService->getAllMedicalHistories($searchRequest);
    }

    #[Route('/patient/{patientId}', name: 'get_medical_history_by_patient', methods: ['GET'])]
    public function getMedicalHistoryByPatient(string $patientId, Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        return $this->medicalHistoryService->getMedicalHistoryByPatient($patientId, $page, $limit);
    }

    #[Route('/doctor/{doctorId}', name: 'get_medical_history_by_doctor', methods: ['GET'])]
    public function getMedicalHistoryByDoctor(string $doctorId, Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        return $this->medicalHistoryService->getMedicalHistoryByDoctor($doctorId, $page, $limit);
    }

    #[Route('/{id}', name: 'get_medical_history_by_id', methods: ['GET'])]
    public function getMedicalHistoryById(string $id): JsonResponse
    {
        return $this->medicalHistoryService->getMedicalHistoryById($id);
    }

    #[Route('', name: 'create_medical_history', methods: ['POST'])]
    public function createMedicalHistory(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->medicalHistoryService->addMedicalHistory($data);
    }

    #[Route('/{id}', name: 'update_medical_history', methods: ['PUT'])]
    public function updateMedicalHistory(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->medicalHistoryService->updateMedicalHistory($id, $data);
    }

    #[Route('/{id}', name: 'delete_medical_history', methods: ['DELETE'])]
    public function deleteMedicalHistory(string $id): JsonResponse
    {
        return $this->medicalHistoryService->deleteMedicalHistory($id);
    }
}
