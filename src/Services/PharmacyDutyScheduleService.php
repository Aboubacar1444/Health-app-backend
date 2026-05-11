<?php

namespace App\Services;

use App\Entity\PharmacyDutySchedule;
use App\Repository\PharmacyDutyScheduleRepository;
use App\Repository\PharmacyRepository;
use App\Repository\CommuneRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class PharmacyDutyScheduleService
{
    public function __construct(
        private readonly PharmacyDutyScheduleRepository $dutyScheduleRepository,
        private readonly PharmacyRepository $pharmacyRepository,
        private readonly CommuneRepository $communeRepository,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
        private readonly PaginationService $paginationService,
    ) {}

    public function createDutySchedule(string $pharmacyId, array $data): JsonResponse
    {
        dd($pharmacyId);
        $pharmacy = $this->pharmacyRepository->find($pharmacyId);
        if (!$pharmacy) {
            return $this->responsesService->errorResponse("Pharmacie introuvable");
        }
        $data['pharmacyId'] = $data['pharmacyId'] ? Uuid::fromString($data['pharmacyId']) : null;
        $dutySchedule = $this->serializer->deserialize(json_encode($data), PharmacyDutySchedule::class, 'json');
        $dutySchedule->setPharmacyId($pharmacy->getId());
        $dutySchedule = $this->entityHelper->save($dutySchedule);

        $body = json_decode($this->serializer->serialize($dutySchedule, 'json', ["groups" => "duty_schedule"]), true);
        return $this->responsesService->successResponse($body, "Planning de garde créé");
    }

    public function getAllDutySchedules(int $page = 1, int $limit = 10): JsonResponse
    {
        $queryBuilder = $this->dutyScheduleRepository->createQueryBuilder('ds')
            ->where('ds.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('ds.startDate', 'ASC');
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ["duty_schedule"]);
    }

    public function updateDutySchedule(string $id, array $data): JsonResponse
    {
        $dutySchedule = $this->dutyScheduleRepository->find($id);
        if (!$dutySchedule) {
            return $this->responsesService->errorResponse("Planning de garde introuvable");
        }

        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($dutySchedule, $setter)) {
                if ($key === 'pharmacyId' && is_string($value)) {
                    $value = Uuid::fromString($value);
                }
                $dutySchedule->$setter($value);
            }
        }

        $dutySchedule = $this->entityHelper->update($dutySchedule);
        $body = json_decode($this->serializer->serialize($dutySchedule, 'json', ["groups" => "duty_schedule"]), true);
        return $this->responsesService->successResponse($body, "Planning de garde mis à jour");
    }

    public function deleteDutySchedule(string $id): JsonResponse
    {
        $dutySchedule = $this->dutyScheduleRepository->find($id);
        if (!$dutySchedule) {
            return $this->responsesService->errorResponse("Planning de garde introuvable");
        }

        $this->entityHelper->remove($dutySchedule);
        return $this->responsesService->successResponse([], "Planning de garde supprimé");
    }

    public function getOnDutyPharmacies(?string $commune = null, int $page = 1, int $limit = 10): JsonResponse
    {
        $today = new \DateTimeImmutable();
        
        $queryBuilder = $this->dutyScheduleRepository->createQueryBuilder('ds')
            ->where('ds.startDate <= :today')
            ->andWhere('ds.endDate >= :today')
            ->andWhere('ds.isActive = :active')
            ->setParameter('today', $today)
            ->setParameter('active', true);

        if ($commune) {
            $communeEntity = $this->communeRepository->createQueryBuilder('c')
                ->where('c.name LIKE :commune')
                ->setParameter('commune', '%' . $commune . '%')
                ->getQuery()
                ->getOneOrNullResult();
            
            if ($communeEntity) {
                $pharmacyIds = $this->pharmacyRepository->createQueryBuilder('p')
                    ->select('p.id')
                    ->where('p.communeId = :communeId')
                    ->andWhere('p.isActive = :pharmacyActive')
                    ->setParameter('communeId', $communeEntity->getId())
                    ->setParameter('pharmacyActive', true)
                    ->getQuery()
                    ->getResult();
                
                $ids = array_column($pharmacyIds, 'id');
                if (!empty($ids)) {
                    $queryBuilder->andWhere('ds.pharmacyId IN (:pharmacyIds)')
                        ->setParameter('pharmacyIds', $ids);
                } else {
                    $queryBuilder->andWhere('1 = 0'); // Aucun résultat
                }
            } else {
                $queryBuilder->andWhere('1 = 0'); // Commune introuvable
            }
        }

        $response = $this->paginationService->paginate($queryBuilder, $page, $limit, ["duty_schedule"]);
        return $this->enrichWithPharmacies($response);
    }

    private function enrichWithPharmacies(JsonResponse $response): JsonResponse
    {
        $data = json_decode($response->getContent(), true);
        
        if (isset($data['body']['data'])) {
            foreach ($data['body']['data'] as &$dutySchedule) {
                if (isset($dutySchedule['pharmacyId'])) {
                    $pharmacy = $this->pharmacyRepository->find($dutySchedule['pharmacyId']);
                    if ($pharmacy) {
                        $dutySchedule['pharmacy'] = json_decode(
                            $this->serializer->serialize($pharmacy, 'json', ["groups" => "pharmacy"]), 
                            true
                        );
                        
                        // Enrichir la pharmacie avec sa commune
                        if (isset($dutySchedule['pharmacy']['communeId'])) {
                            $commune = $this->communeRepository->find($dutySchedule['pharmacy']['communeId']);
                            if ($commune) {
                                $dutySchedule['pharmacy']['commune'] = json_decode(
                                    $this->serializer->serialize($commune, 'json', ["groups" => "commune"]), 
                                    true
                                );
                            }
                            unset($dutySchedule['pharmacy']['communeId']);
                        }
                    }
                }
            }
        }
        
        return new JsonResponse($data, $response->getStatusCode());
    }
}