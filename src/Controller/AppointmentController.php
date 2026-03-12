<?php

namespace App\Controller;

use App\Request\AppointmentSearchRequest;
use App\Services\AppointmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/appointments')]
class AppointmentController extends AbstractController
{
    public function __construct(
        private readonly AppointmentService $appointmentService
    ) {}

    #[Route('', name: 'app_appointment_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->appointmentService->addAppointment($data);
    }

    #[Route('/search', name: 'app_appointment_search', methods: ['POST'])]
    public function search(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $searchRequest = new AppointmentSearchRequest($data);
        return $this->appointmentService->getAllAppointments($searchRequest);
    }

    #[Route('/patient/{patientId}', name: 'app_appointment_patient', methods: ['GET'])]
    public function getPatientAppointments(string $patientId, Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        return $this->appointmentService->getPatientAppointments($patientId, $page, $limit);
    }

    #[Route('/doctor/{doctorId}', name: 'app_appointment_doctor', methods: ['GET'])]
    public function getDoctorAppointments(string $doctorId, Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        return $this->appointmentService->getDoctorAppointments($doctorId, $page, $limit);
    }

    #[Route('/{id}', name: 'app_appointment_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        return $this->appointmentService->getAppointmentById($id);
    }

    #[Route('/{id}', name: 'app_appointment_update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->appointmentService->updateAppointment($id, $data);
    }

    #[Route('/{id}/cancel', name: 'app_appointment_cancel', methods: ['PUT'])]
    public function cancel(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $cancelledBy = $data['cancelledBy'] ?? '';
        $reason = $data['reason'] ?? '';
        return $this->appointmentService->cancelAppointment($id, $cancelledBy, $reason);
    }

    #[Route('/{id}/confirm', name: 'app_appointment_confirm', methods: ['PUT'])]
    public function confirm(string $id): JsonResponse
    {
        return $this->appointmentService->confirmAppointment($id);
    }

    #[Route('/{id}/complete', name: 'app_appointment_complete', methods: ['PUT'])]
    public function complete(string $id): JsonResponse
    {
        return $this->appointmentService->completeAppointment($id);
    }
}