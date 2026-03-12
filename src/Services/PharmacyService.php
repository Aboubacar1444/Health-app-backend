<?php

namespace App\Services;

use App\Entity\Pharmacy;
use App\Repository\PharmacyRepository;
use App\Repository\CommuneRepository;
use App\Repository\PharmacyDutyScheduleRepository;
use Symfony\Component\Uid\Uuid;
use App\Request\PharmacySearchRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

final class PharmacyService
{
    public function __construct(
        private readonly PharmacyRepository $pharmacyRepository,
        private readonly CommuneRepository $communeRepository,
        private readonly PharmacyDutyScheduleRepository $dutyScheduleRepository,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
        private readonly PaginationService $paginationService,
    ) {}

    public function addPharmacy(array $data): JsonResponse
    {
        if (!$data || empty($data)) {
            return $this->responsesService->errorResponse("Données invalides");
        }
        $data['communeId'] = $data['communeId'] ? Uuid::fromString($data['communeId']) : null;
        $pharmacy = $this->serializer->deserialize(json_encode($data), Pharmacy::class, 'json');
        $pharmacy = $this->entityHelper->save($pharmacy);

        $body = json_decode($this->serializer->serialize($pharmacy, 'json', ["groups" => "pharmacy"]), true);
        return $this->responsesService->successResponse($body, "Pharmacie créée avec succès");
    }

    public function getAllPharmacies(?PharmacySearchRequest $searchRequest = null): JsonResponse
    {
        $queryBuilder = $this->pharmacyRepository->createQueryBuilder('p');
        
        if ($searchRequest) {
            $this->applyPharmacyFilters($queryBuilder, $searchRequest);
            $result = $this->paginationService->paginate($queryBuilder, $searchRequest->page, $searchRequest->limit, ["pharmacy"]);
            
            $result = $this->enrichWithRelations($result);
            
            return $result;
        }
        
        return $this->paginationService->paginate($queryBuilder, 1, 10, ["pharmacy"]);
    }

    private function applyPharmacyFilters($queryBuilder, PharmacySearchRequest $searchRequest): void
    {
        if ($searchRequest->name) {
            $queryBuilder->andWhere('p.name LIKE :name')
                ->setParameter('name', '%' . $searchRequest->name . '%');
        }
        
        if ($searchRequest->city) {
            $queryBuilder->andWhere('p.city = :city')
                ->setParameter('city', $searchRequest->city);
        }
        
        if ($searchRequest->commune) {
            $commune = $this->communeRepository->createQueryBuilder('c')
                ->where('c.name LIKE :commune')
                ->setParameter('commune', '%' . $searchRequest->commune . '%')
                ->getQuery()
                ->getOneOrNullResult();
            
            if ($commune) {
                $queryBuilder->andWhere('p.communeId = :communeId')
                    ->setParameter('communeId', $commune->getId());
            } else {
                $queryBuilder->andWhere('1 = 0'); // Aucun résultat si commune introuvable
            }
        }
        
        if ($searchRequest->isOpen24h !== null) {
            $queryBuilder->andWhere('p.isOpen24h = :isOpen24h')
                ->setParameter('isOpen24h', $searchRequest->isOpen24h);
        }
        
        if ($searchRequest->hasDelivery !== null) {
            $queryBuilder->andWhere('p.hasDelivery = :hasDelivery')
                ->setParameter('hasDelivery', $searchRequest->hasDelivery);
        }
        
        if ($searchRequest->isActive !== null) {
            $queryBuilder->andWhere('p.isActive = :isActive')
                ->setParameter('isActive', $searchRequest->isActive);
        }
        
        if ($searchRequest->isOnDuty !== null && $searchRequest->isOnDuty) {
            $today = new \DateTimeImmutable();
            
            // JOIN simple sans addSelect
            $queryBuilder->join('App\Entity\PharmacyDutySchedule', 'pds', 'WITH', 'pds.pharmacyId = p.id')
                ->andWhere('pds.startDate <= :today')
                ->andWhere('pds.endDate >= :today')
                ->andWhere('pds.isActive = :active')
                ->setParameter('today', $today)
                ->setParameter('active', true);
        }
        
        if ($searchRequest->openOnHolidays !== null) {
            $queryBuilder->andWhere('p.openOnHolidays = :openOnHolidays')
                ->setParameter('openOnHolidays', $searchRequest->openOnHolidays);
        }
        
        if ($searchRequest->minRating) {
            $queryBuilder->andWhere('p.rating >= :minRating')
                ->setParameter('minRating', $searchRequest->minRating);
        }
        
        if ($searchRequest->service) {
            $queryBuilder->andWhere('JSON_CONTAINS(p.services, :service) = 1')
                ->setParameter('service', json_encode($searchRequest->service));
        }
        
        if ($searchRequest->search) {
            $queryBuilder->andWhere('p.name LIKE :search OR p.address LIKE :search')
                ->setParameter('search', '%' . $searchRequest->search . '%');
        }
    }

    public function getPharmacyById(string $id): JsonResponse
    {
        $pharmacy = $this->pharmacyRepository->find($id);
        
        if (!$pharmacy) {
            return $this->responsesService->errorResponse("Pharmacie introuvable");
        }
        
        $body = json_decode($this->serializer->serialize($pharmacy, 'json', ["groups" => "pharmacy"]), true);
        
        if ($body === null) {
            return $this->responsesService->errorResponse("Erreur de sérialisation");
        }
        
        // Enrichir avec le dutySchedule
        $this->addDutyScheduleToPharmacy($body);
        
        // Enrichir avec la commune
        if (isset($body['communeId']) && $body['communeId']) {
            $commune = $this->communeRepository->find($body['communeId']);
            if ($commune) {
                $body['commune'] = json_decode(
                    $this->serializer->serialize($commune, 'json', ["groups" => "commune"]), 
                    true
                );
            } else {
                $body['commune'] = null;
            }
            unset($body['communeId']);
        }
        
        return $this->responsesService->successResponse($body, "Pharmacie trouvée");
    }
    
    private function addDutyScheduleToPharmacy(array &$pharmacy): void
    {
        if (isset($pharmacy['id'])) {
            $today = new \DateTimeImmutable();
            $pharmacyUuid = Uuid::fromString($pharmacy['id']);
            
            $dutySchedules = $this->dutyScheduleRepository->findBy([
                'pharmacyId' => $pharmacyUuid,
                'isActive' => true
            ]);
            
            $dutySchedule = null;
            foreach ($dutySchedules as $schedule) {
                if ($schedule->getStartDate() <= $today && $schedule->getEndDate() >= $today) {
                    $dutySchedule = $schedule;
                    break;
                }
            }
            
            if ($dutySchedule) {
                $pharmacy['dutySchedule'] = json_decode(
                    $this->serializer->serialize($dutySchedule, 'json', ["groups" => "duty_schedule"]), 
                    true
                );
                $pharmacy['currentlyOnDuty'] = true;
            } else {
                $pharmacy['dutySchedule'] = null;
                $pharmacy['currentlyOnDuty'] = false;
            }
        }
    }

    public function updatePharmacy(string $id, array $data): JsonResponse
    {
        $pharmacy = $this->pharmacyRepository->find($id);
        if (!$pharmacy) {
            return $this->responsesService->errorResponse("Pharmacie introuvable");
        }

        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($pharmacy, $setter)) {
                $pharmacy->$setter($value);
            }
        }

        $pharmacy = $this->entityHelper->update($pharmacy);
        $body = json_decode($this->serializer->serialize($pharmacy, 'json', ["groups" => "pharmacy"]), true);
        return $this->responsesService->successResponse($body, "Pharmacie mise à jour");
    }

    public function removePharmacy(string $id): JsonResponse
    {
        $pharmacy = $this->pharmacyRepository->find($id);
        if (!$pharmacy) {
            return $this->responsesService->errorResponse("Pharmacie introuvable");
        }

        $this->entityHelper->remove($pharmacy);
        return $this->responsesService->successResponse([], "Pharmacie supprimée");
    }

    public function getActivePharmacies(int $page = 1, int $limit = 10): JsonResponse
    {
        $queryBuilder = $this->pharmacyRepository->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->setParameter('active', true);
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ["pharmacy"]);
    }

    public function getPharmaciesByCity(string $city, int $page = 1, int $limit = 10): JsonResponse
    {
        $queryBuilder = $this->pharmacyRepository->createQueryBuilder('p')
            ->where('p.city = :city')
            ->andWhere('p.isActive = :active')
            ->setParameter('city', $city)
            ->setParameter('active', true);
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ["pharmacy"]);
    }

    public function getOpen24hPharmacies(int $page = 1, int $limit = 10): JsonResponse
    {
        $queryBuilder = $this->pharmacyRepository->createQueryBuilder('p')
            ->where('p.isOpen24h = :open24h')
            ->andWhere('p.isActive = :active')
            ->setParameter('open24h', true)
            ->setParameter('active', true);
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ["pharmacy"]);
    }

    public function getPharmaciesByCommune(string $commune, int $page = 1, int $limit = 10): JsonResponse
    {
        $communeEntity = $this->communeRepository->createQueryBuilder('c')
            ->where('c.name LIKE :commune')
            ->setParameter('commune', '%' . $commune . '%')
            ->getQuery()
            ->getOneOrNullResult();
        
        if (!$communeEntity) {
            return $this->responsesService->errorResponse("Commune introuvable");
        }
        
        $queryBuilder = $this->pharmacyRepository->createQueryBuilder('p')
            ->where('p.communeId = :communeId')
            ->andWhere('p.isActive = :active')
            ->setParameter('communeId', $communeEntity->getId())
            ->setParameter('active', true);
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ["pharmacy"]);
    }

    private function enrichWithRelations(JsonResponse $response): JsonResponse
    {
        $data = json_decode($response->getContent(), true);
        
        if (isset($data['body']['data'])) {
            foreach ($data['body']['data'] as &$pharmacy) {
                // Enrichir avec la commune
                if (isset($pharmacy['communeId']) && $pharmacy['communeId']) {
                    $commune = $this->communeRepository->find($pharmacy['communeId']);
                    if ($commune) {
                        $pharmacy['commune'] = json_decode(
                            $this->serializer->serialize($commune, 'json', ["groups" => "commune"]), 
                            true
                        );
                    } else {
                        $pharmacy['commune'] = null;
                    }
                    unset($pharmacy['communeId']); // Supprimer l'ID
                }
                
                // Enrichir avec le schedule de garde actuel (toujours)
                if (isset($pharmacy['id'])) {
                    $today = new \DateTimeImmutable();
                    $pharmacyUuid = Uuid::fromString($pharmacy['id']);
                    
                    $dutySchedules = $this->dutyScheduleRepository->findBy([
                        'pharmacyId' => $pharmacyUuid,
                        'isActive' => true
                    ]);
                    
                    $dutySchedule = null;
                    foreach ($dutySchedules as $schedule) {
                        if ($schedule->getStartDate() <= $today && $schedule->getEndDate() >= $today) {
                            $dutySchedule = $schedule;
                            break;
                        }
                    }
                    
                    if ($dutySchedule) {
                        $pharmacy['dutySchedule'] = json_decode(
                            $this->serializer->serialize($dutySchedule, 'json', ["groups" => "duty_schedule"]), 
                            true
                        );
                    } else {
                        $pharmacy['dutySchedule'] = null;
                    }
                }
            }
        }
        
        return new JsonResponse($data, $response->getStatusCode());
    }
}