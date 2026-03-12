<?php

namespace App\Services;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

final class PaginationService
{
    public function __construct(
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
    ) {}

    public function paginate(QueryBuilder $queryBuilder, int $page = 1, int $limit = 10, array $groups = []): JsonResponse
    {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        
        $totalQuery = clone $queryBuilder;
        // Récupérer l'alias racine du QueryBuilder
        $rootAliases = $totalQuery->getRootAliases();
        $rootAlias = $rootAliases[0];
        
        $total = $totalQuery->select("COUNT(DISTINCT {$rootAlias}.id)")->getQuery()->getSingleScalarResult();
        
        $offset = ($page - 1) * $limit;
        $results = $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $data = json_decode($this->serializer->serialize($results, 'json', ["groups" => $groups]), true);
        
        $response = [
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => (int) $total,
                'total_pages' => (int) ceil($total / $limit),
                'has_next' => $page < ceil($total / $limit),
                'has_prev' => $page > 1
            ],
            'data' => $data,
        ];

        return $this->responsesService->successResponse($response, "Données paginées");
    }
}