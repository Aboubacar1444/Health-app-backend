<?php

namespace App\Controller;

use App\Services\DoctorEstablishmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/doctor-establishments')]
final class DoctorEstablishmentController extends AbstractController
{
    public function __construct(private readonly DoctorEstablishmentService $doctorEstablishmentService) {}

    #[Route('', name: 'app_doctor_establishment_create', methods: "POST")]
    public function addDoctorEstablishment(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->doctorEstablishmentService->addDoctorEstablishment($data);
    }

    #[Route('/doctor/{doctorId}', name: 'app_doctor_establishment_by_doctor', methods: "GET")]
    public function getDoctorEstablishments(string $doctorId): JsonResponse
    {
        return $this->doctorEstablishmentService->getDoctorEstablishments($doctorId);
    }


    #[Route('/{id}', name: 'app_doctor_establishment_update', methods: "PUT")]
    public function updateDoctorEstablishment(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->doctorEstablishmentService->updateDoctorEstablishment($id, $data);
    }

    #[Route('/{id}', name: 'app_doctor_establishment_delete', methods: "DELETE")]
    public function removeDoctorEstablishment(string $id): JsonResponse
    {
        return $this->doctorEstablishmentService->removeDoctorEstablishment($id);
    }
}