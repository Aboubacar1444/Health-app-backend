<?php

namespace App\Controller;

use App\Entity\HealthTip;
use App\Entity\Notification;
use App\Repository\HealthTipRepository;
use App\Repository\UserRepository;
use App\Services\ResponsesService;
use App\Services\EntityHelperService;
use App\Services\PaginationService;
use App\Services\UploadFileService;
use App\Utils\HealthTipCategory;
use App\Utils\NotificationPriority;
use App\Utils\NotificationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

#[Route('/health-tip')]
class HealthTipController extends AbstractController
{
    public function __construct(
        private readonly HealthTipRepository $healthTipRepository,
        private readonly UserRepository $userRepository,
        private readonly ResponsesService $responsesService,
        private readonly UploadFileService $uploadFileService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
        private readonly PaginationService $paginationService,
    ) {}

    #[Route('', name: 'get_all_health_tips', methods: ['GET'])]
    public function getAllHealthTips(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        
        $queryBuilder = $this->healthTipRepository->createQueryBuilder('ht')
            ->where('ht.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('ht.isFeatured', 'DESC')
            ->addOrderBy('ht.publishedAt', 'DESC');
            
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ['health_tip']);
    }

    #[Route('/featured', name: 'get_featured_health_tips', methods: ['GET'])]
    public function getFeaturedHealthTips(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 5);
        
        $tips = $this->healthTipRepository->findBy(
            ['isPublished' => true, 'isFeatured' => true],
            ['publishedAt' => 'DESC'],
            $limit
        );
        
        $body = json_decode($this->serializer->serialize($tips, 'json', ["groups" => "health_tip"]), true);
        return $this->responsesService->successResponse($body, "Conseils mis en avant récupérés");
    }

    #[Route('/category/{category}', name: 'get_health_tips_by_category', methods: ['GET'])]
    public function getHealthTipsByCategory(string $category, Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        
        $queryBuilder = $this->healthTipRepository->createQueryBuilder('ht')
            ->where('ht.isPublished = :published')
            ->andWhere('ht.category = :category')
            ->setParameter('published', true)
            ->setParameter('category', strtoupper($category))
            ->orderBy('ht.publishedAt', 'DESC');
            
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ['health_tip']);
    }

    #[Route('/{id}', name: 'get_health_tip_by_id', methods: ['GET'])]
    public function getHealthTipById(string $id): JsonResponse
    {
        $tip = $this->healthTipRepository->find($id);
        
        if (!$tip || !$tip->isPublished()) {
            return $this->responsesService->errorResponse("Conseil santé introuvable");
        }
        
        // Incrémenter le compteur de vues
        $tip->incrementViewsCount();
        $this->entityHelper->update($tip);
        
        $body = json_decode($this->serializer->serialize($tip, 'json', ["groups" => "health_tip"]), true);
        return $this->responsesService->successResponse($body, "Conseil santé trouvé");
    }

    #[Route('', name: 'create_health_tip', methods: ['POST'])]
    public function createHealthTip(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if($file) {
            $data = json_decode($request->request->get('data'), true);
            $data['imageUrl'] = $this->uploadFileService->uploadFile($file);
        }else{
            $data = json_decode($request->getContent(), true);
            if (!$data || empty($data)) {
                return $this->responsesService->errorResponse("Données invalides");
            }
        }

        $tip = $this->serializer->deserialize(json_encode($data), HealthTip::class, 'json');
        $tip = $this->entityHelper->save($tip);
        $this->createHealthTipNotifications($tip);

        $body = json_decode($this->serializer->serialize($tip, 'json', ["groups" => "health_tip"]), true);
        return $this->responsesService->successResponse($body, "Conseil santé créé avec succès");
    }

    #[Route('/{id}', name: 'update_health_tip', methods: ['PUT'])]
    public function updateHealthTip(string $id, Request $request): JsonResponse
    {
        $tip = $this->healthTipRepository->find($id);
        if (!$tip) {
            return $this->responsesService->errorResponse("Conseil santé introuvable");
        }
        $file = $request->files->get('file');
        if($file) {
            $data = json_decode($request->request->get('data'), true);
            $data['imageUrl'] = $this->uploadFileService->uploadFile($file, "PublicityImages");

        }
        else 
            $data = json_decode($request->getContent(), true);
        foreach ($data as $key => $value) {
           if ($key === 'category') {
                $value = strtoupper($value);
                $value = HealthTipCategory::from($value);
            }
            if ($key === 'publishedAt' && !is_null($value)) {
                $value = new \DateTime($value);
            }
            if ($key === 'authorId' && is_string($value)) {
                $value = Uuid::fromString($value);
                
            }
            $setter = 'set' . ucfirst($key);
            if (method_exists($tip, $setter)) {
                
                $tip->$setter($value);
            }
        }

        $tip = $this->entityHelper->update($tip);
        $body = json_decode($this->serializer->serialize($tip, 'json', ["groups" => "health_tip"]), true);
        return $this->responsesService->successResponse($body, "Conseil santé mis à jour");
    }

    #[Route('/{id}', name: 'delete_health_tip', methods: ['DELETE'])]
    public function deleteHealthTip(string $id): JsonResponse
    {
        $tip = $this->healthTipRepository->find($id);
        if (!$tip) {
            return $this->responsesService->errorResponse("Conseil santé introuvable");
        }

        $this->entityHelper->remove($tip);
        return $this->responsesService->successResponse([], "Conseil santé supprimé");
    }

    #[Route('/{id}/like', name: 'like_health_tip', methods: ['POST'])]
    public function likeHealthTip(string $id): JsonResponse
    {
        $tip = $this->healthTipRepository->find($id);
        if (!$tip) {
            return $this->responsesService->errorResponse("Conseil santé introuvable");
        }

        $tip->incrementLikesCount();
        $this->entityHelper->update($tip);

        return $this->responsesService->successResponse(['likesCount' => $tip->getLikesCount()], "Like ajouté");
    }

    #[Route('/{id}/unlike', name: 'unlike_health_tip', methods: ['POST'])]
    public function unlikeHealthTip(string $id): JsonResponse
    {
        $tip = $this->healthTipRepository->find($id);
        if (!$tip) {
            return $this->responsesService->errorResponse("Conseil santé introuvable");
        }

        $tip->decrementLikesCount();
        $this->entityHelper->update($tip);

        return $this->responsesService->successResponse(['likesCount' => $tip->getLikesCount()], "Like retiré");
    }

    private function createHealthTipNotifications(HealthTip $tip): void
    {
        if (!$tip->isPublished()) {
            return;
        }

        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.isActivated = :active OR u.isActivated IS NULL')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        if ($users === []) {
            return;
        }

        $notifications = [];
        $authorId = $tip->getAuthorId()?->toRfc4122();
        $payload = [
            'healthTipId' => $tip->getId()?->toRfc4122(),
            'category' => $tip->getCategory()?->value,
            'type' => 'HEALTH_TIP',
        ];

        foreach ($users as $user) {
            $userId = $user->getId();
            if (!$userId) {
                continue;
            }

            if ($authorId !== null && $authorId === $userId->toRfc4122()) {
                continue;
            }

            $notification = new Notification();
            $notification->setUserId($userId)
                ->setType(NotificationType::REMINDER)
                ->setTitle('Nouveau conseil santé')
                ->setMessage($tip->getTitle() ?? 'Un nouveau conseil santé est disponible.')
                ->setData($payload)
                ->setPriority(NotificationPriority::LOW)
                ->setIsRead(false);

            $notifications[] = $notification;
        }

        if ($notifications !== []) {
            $this->entityHelper->saveMultiple($notifications);
        }
    }
}
