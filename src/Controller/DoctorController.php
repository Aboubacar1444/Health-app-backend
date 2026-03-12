<?php

namespace App\Controller;

use App\Services\DoctorService;
use App\Request\DoctorSearchRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/doctors')]
final class DoctorController extends AbstractController
{
    public function __construct(private readonly DoctorService $doctorService) {}

    #[Route('', name: 'app_doctor_create', methods: "POST")]
    public function addDoctor(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $data['userId'] = Uuid::fromString($data['userId']);
        
        return $this->doctorService->addDoctor($data);
    }

    #[Route('/search', name: 'app_doctor_search', methods: "POST")]
    public function searchDoctors(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $searchRequest = new DoctorSearchRequest($data);
        return $this->doctorService->getAllDoctors($searchRequest);
    }

    // #[Route('/verified', name: 'app_doctor_verified', methods: "GET")]
    // public function getVerifiedDoctors(): JsonResponse
    // {
    //     return $this->doctorService->getVerifiedDoctors();
    // }

    // #[Route('/speciality/{speciality}', name: 'app_doctor_by_speciality', methods: "GET")]
    // public function getDoctorsBySpeciality(string $speciality): JsonResponse
    // {
    //     return $this->doctorService->getDoctorsBySpeciality($speciality);
    // }

    #[Route('/user/{id}', name: 'app_doctor_show_by_user_id', methods: "GET")]
    public function getDoctorByUserId(string $id): JsonResponse
    {
        return $this->doctorService->getDoctorByUserId($id);
    }

    #[Route('/{id}', name: 'app_doctor_show', methods: "GET")]
    public function getDoctorById(string $id): JsonResponse
    {
        return $this->doctorService->getDoctorById($id);
    }

    #[Route('/{id}', name: 'app_doctor_update', methods: "PUT")]
    public function updateDoctor(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->doctorService->updateDoctor($id, $data);
    }

    #[Route('/{id}', name: 'app_doctor_delete', methods: "DELETE")]
    public function removeDoctor(string $id): JsonResponse
    {
        return $this->doctorService->removeDoctor($id);
    }

    #[Route('/{id}/verify', name: 'app_doctor_verify', methods: "PATCH")]
    public function verifyDoctor(string $id): JsonResponse
    {
        return $this->doctorService->verifyDoctor($id);
    }
}