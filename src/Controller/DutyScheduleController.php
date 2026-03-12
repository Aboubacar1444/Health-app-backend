<?php

namespace App\Controller;

use App\Services\PharmacyDutyScheduleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/duty-schedules')]
class DutyScheduleController extends AbstractController
{
    public function __construct(
        private readonly PharmacyDutyScheduleService $dutyScheduleService
    ) {}

    #[Route('', name: 'app_duty_schedule_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        return $this->dutyScheduleService->getAllDutySchedules($page, $limit);
    }

    #[Route('/{id}', name: 'app_duty_schedule_update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->dutyScheduleService->updateDutySchedule($id, $data);
    }

    #[Route('/{id}', name: 'app_duty_schedule_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        return $this->dutyScheduleService->deleteDutySchedule($id);
    }
}