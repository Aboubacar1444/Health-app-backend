<?php

namespace App\Services;

use App\Entity\Establishment;
use App\Repository\EstablishmentRepository;
use App\Request\EstablishmentSearchRequest;
use App\Utils\EstablishmentType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

final class EstablishmentService
{
    public function __construct(
        private readonly EstablishmentRepository $establishmentRepository,
        private readonly ResponsesService $responsesService,
        private readonly UploadFileService $uploadFileService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
        private readonly PaginationService $paginationService,
    ) {}

    public function addEstablishment(array $data, $file = null): JsonResponse
    {
        sleep(2);
        if ((!$data || empty($data)) && !$file) {
            return $this->responsesService->errorResponse("Données invalides");
        }
        if (array_key_exists('insurances', $data)) {
            $error = $this->validateInsurances($data['insurances']);
            if ($error) {
                return $this->responsesService->errorResponse($error);
            }
        }
        if ($file) {
            try {
                $fileName = $this->uploadFileService->uploadFile($file, 'EstablishmentImages');
                $data['image'] = $fileName;
            } catch (\Exception $e) {
                return $this->responsesService->errorResponse($e->getMessage());
            }
        }

        $data['type'] = EstablishmentType::tryFrom($data['type'])?->value ?? null;
        $establishment = $this->serializer->deserialize(json_encode($data), Establishment::class, 'json');
        
     
        $establishment = $this->entityHelper->save($establishment);

        $body = json_decode($this->serializer->serialize($establishment, 'json', ["groups" => "establishment"]), true);
        return $this->responsesService->successResponse($body, "Établissement créé avec succès");
    }

    public function getAllEstablishments(?EstablishmentSearchRequest $searchRequest = null): JsonResponse
    {
        $queryBuilder = $this->establishmentRepository->createQueryBuilder('e');
        
        if ($searchRequest) {
            $this->applyEstablishmentFilters($queryBuilder, $searchRequest);
            return $this->paginationService->paginate($queryBuilder, $searchRequest->page, $searchRequest->limit, ["establishment"]);
        }
        
        return $this->paginationService->paginate($queryBuilder, 1, 10, ["establishment"]);
    }

    private function applyEstablishmentFilters($queryBuilder, EstablishmentSearchRequest $searchRequest): void
    {
        if ($searchRequest->name) {
            $queryBuilder->andWhere('e.name LIKE :name')
                ->setParameter('name', '%' . $searchRequest->name . '%');
        }
        
        if ($searchRequest->type) {
            $queryBuilder->andWhere('e.type = :type')
                ->setParameter('type', $searchRequest->type);
        }
        
        if ($searchRequest->city) {
            $queryBuilder->andWhere('e.city = :city')
                ->setParameter('city', $searchRequest->city);
        }
        
        if ($searchRequest->isPublic !== null) {
            $queryBuilder->andWhere('e.isPublic = :isPublic')
                ->setParameter('isPublic', $searchRequest->isPublic);
        }
        
        if ($searchRequest->isEmergency !== null) {
            $queryBuilder->andWhere('e.isEmergency = :isEmergency')
                ->setParameter('isEmergency', $searchRequest->isEmergency);
        }
        
        if ($searchRequest->isActive !== null) {
            $queryBuilder->andWhere('e.isActive = :isActive')
                ->setParameter('isActive', $searchRequest->isActive);
        }
        
        if ($searchRequest->minRating) {
            $queryBuilder->andWhere('e.rating >= :minRating')
                ->setParameter('minRating', $searchRequest->minRating);
        }
        
        if ($searchRequest->service) {
            $queryBuilder->andWhere('JSON_CONTAINS(e.services, :service) = 1')
                ->setParameter('service', json_encode($searchRequest->service));
        }
        
        if ($searchRequest->search) {
            $queryBuilder->andWhere('e.name LIKE :search OR e.address LIKE :search')
                ->setParameter('search', '%' . $searchRequest->search . '%');
        }
    }

    public function getEstablishmentById(string $id): JsonResponse
    {
        $establishment = $this->establishmentRepository->find($id);
        
        if (!$establishment) {
            return $this->responsesService->errorResponse("Établissement introuvable");
        }
        
        $body = json_decode($this->serializer->serialize($establishment, 'json', ["groups" => "establishment"]), true);
        
        if ($body === null) {
            return $this->responsesService->errorResponse("Erreur de sérialisation");
        }
        
        return $this->responsesService->successResponse($body, "Établissement trouvé");
    }

    public function updateEstablishment(string $id, array $data, $file = null): JsonResponse
    {
        $establishment = $this->establishmentRepository->find($id);
        if (!$establishment) {
            return $this->responsesService->errorResponse("Établissement introuvable");
        }
        if (array_key_exists('insurances', $data)) {
            $error = $this->validateInsurances($data['insurances']);
            if ($error) {
                return $this->responsesService->errorResponse($error);
            }
        }
        if ($file) {
            try {
                $fileName = $this->uploadFileService->uploadFile($file, 'EstablishmentImages');
                $data['image'] = $fileName;
            } catch (\Exception $e) {
                return $this->responsesService->errorResponse($e->getMessage());
            }
        }
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($establishment, $setter)) {
                $establishment->$setter($value);
            }
        }


        $establishment = $this->entityHelper->update($establishment);
        $body = json_decode($this->serializer->serialize($establishment, 'json', ["groups" => "establishment"]), true);
        return $this->responsesService->successResponse($body, "Établissement mis à jour");
    }

    private function validateInsurances(mixed $insurances): ?string
    {
        if ($insurances === null) {
            return null;
        }

        if (!is_array($insurances)) {
            return "Le champ insurances doit être un tableau d'objets";
        }

        foreach ($insurances as $index => $insurance) {
            if (!is_array($insurance)) {
                return "Chaque assurance doit être un objet (index $index)";
            }

            $name = $insurance['name'] ?? null;
            $taux = $insurance['taux'] ?? null;

            if (!is_string($name) || trim($name) === '') {
                return "Le champ name est obligatoire pour chaque assurance (index $index)";
            }

            if ($taux === null || $taux === '') {
                return "Le champ taux est obligatoire pour chaque assurance (index $index)";
            }

            if (!is_numeric($taux)) {
                return "Le champ taux doit être numérique (index $index)";
            }
        }

        return null;
    }

    public function removeEstablishment(string $id): JsonResponse
    {
        $establishment = $this->establishmentRepository->find($id);
        if (!$establishment) {
            return $this->responsesService->errorResponse("Établissement introuvable");
        }

        $this->entityHelper->remove($establishment);
        return $this->responsesService->successResponse([], "Établissement supprimé");
    }

    public function getActiveEstablishments(int $page = 1, int $limit = 10): JsonResponse
    {
        $queryBuilder = $this->establishmentRepository->createQueryBuilder('e')
            ->where('e.isActive = :active')
            ->setParameter('active', true);
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ["establishment"]);
    }

    public function getEstablishmentsByCity(string $city, int $page = 1, int $limit = 10): JsonResponse
    {
        $queryBuilder = $this->establishmentRepository->createQueryBuilder('e')
            ->where('e.city = :city')
            ->andWhere('e.isActive = :active')
            ->setParameter('city', $city)
            ->setParameter('active', true);
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ["establishment"]);
    }
}
