<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Services\EntityHelperService;
use App\Services\PaginationService;
use App\Services\ResponsesService;
use App\Utils\NotificationPriority;
use App\Utils\NotificationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

#[Route('/notifications')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
        private readonly PaginationService $paginationService,
    ) {}

    #[Route('', name: 'get_all_notifications', methods: ['GET'])]
    public function getAllNotifications(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $queryBuilder = $this->notificationRepository->createQueryBuilder('n')
            ->orderBy('n.isRead', 'ASC')
            ->addOrderBy('n.createdAt', 'DESC');

        return $this->paginationService->paginate($queryBuilder, $page, $limit, ['notification']);
    }

    #[Route('/user/{userId}', name: 'get_notifications_by_user', methods: ['GET'])]
    public function getNotificationsByUser(string $userId, Request $request): JsonResponse
    {
        try {
            $userUuid = Uuid::fromString($userId);
        } catch (\Exception) {
            return $this->responsesService->errorResponse("ID utilisateur invalide");
        }

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $queryBuilder = $this->notificationRepository->createQueryBuilder('n')
            ->where('n.userId = :userId')
            ->setParameter('userId', $userUuid, 'uuid');
        $isReadParam = $request->query->get('isRead');
        if ($isReadParam !== null) {
            $isRead = filter_var($isReadParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isRead !== null) {
                $queryBuilder->andWhere('n.isRead = :isRead')
                    ->setParameter('isRead', $isRead);
            }
        }

        $typeParam = $request->query->get('type');
        if ($typeParam) {
            try {
                $type = NotificationType::from(strtoupper((string) $typeParam));
            } catch (\Exception) {
                return $this->responsesService->errorResponse("Type de notification invalide");
            }
            $queryBuilder->andWhere('n.type = :type')
                ->setParameter('type', $type);
        }

        $priorityParam = $request->query->get('priority');
        if ($priorityParam) {
            try {
                $priority = NotificationPriority::from(strtoupper((string) $priorityParam));
            } catch (\Exception) {
                return $this->responsesService->errorResponse("Priorité invalide");
            }
            $queryBuilder->andWhere('n.priority = :priority')
                ->setParameter('priority', $priority);
        }

        $queryBuilder
            ->orderBy('n.isRead', 'ASC')
            ->addOrderBy('n.createdAt', 'DESC');

        return $this->paginationService->paginate($queryBuilder, $page, $limit, ['notification']);
    }

    #[Route('/{id}', name: 'get_notification_by_id', methods: ['GET'])]
    public function getNotificationById(string $id): JsonResponse
    {
        $notification = $this->notificationRepository->find($id);

        if (!$notification) {
            return $this->responsesService->errorResponse("Notification introuvable");
        }

        $body = json_decode($this->serializer->serialize($notification, 'json', ["groups" => "notification"]), true);
        return $this->responsesService->successResponse($body, "Notification trouvée");
    }

    #[Route('', name: 'create_notification', methods: ['POST'])]
    public function createNotification(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data)) {
            return $this->responsesService->errorResponse("Données invalides");
        }

        $notification = $this->serializer->deserialize(json_encode($data), Notification::class, 'json');
        $notification = $this->entityHelper->save($notification);

        $body = json_decode($this->serializer->serialize($notification, 'json', ["groups" => "notification"]), true);
        return $this->responsesService->successResponse($body, "Notification créée avec succès");
    }

    #[Route('/{id}', name: 'update_notification', methods: ['PUT'])]
    public function updateNotification(string $id, Request $request): JsonResponse
    {
        $notification = $this->notificationRepository->find($id);
        if (!$notification) {
            return $this->responsesService->errorResponse("Notification introuvable");
        }

        $data = json_decode($request->getContent(), true);
        foreach ($data as $key => $value) {
            if ($key === 'type' && is_string($value)) {
                $value = NotificationType::from(strtoupper($value));
            }
            if ($key === 'priority' && is_string($value)) {
                $value = NotificationPriority::from(strtoupper($value));
            }
            $setter = 'set' . ucfirst($key);
            if (method_exists($notification, $setter)) {
                $notification->$setter($value);
            }
        }

        $notification = $this->entityHelper->update($notification);
        $body = json_decode($this->serializer->serialize($notification, 'json', ["groups" => "notification"]), true);
        return $this->responsesService->successResponse($body, "Notification mise à jour");
    }

    #[Route('/{id}/read', name: 'mark_notification_as_read', methods: ['PUT'])]
    public function markNotificationAsRead(string $id): JsonResponse
    {
        $notification = $this->notificationRepository->find($id);
        if (!$notification) {
            return $this->responsesService->errorResponse("Notification introuvable");
        }

        $notification->setIsRead(true);
        $this->entityHelper->update($notification);

        $body = json_decode($this->serializer->serialize($notification, 'json', ["groups" => "notification"]), true);
        return $this->responsesService->successResponse($body, "Notification marquée comme lue");
    }

    #[Route('/user/{userId}/read-all', name: 'mark_all_notifications_as_read', methods: ['PUT'])]
    public function markAllNotificationsAsRead(string $userId): JsonResponse
    {
        try {
            $userUuid = Uuid::fromString($userId);
        } catch (\Exception) {
            return $this->responsesService->errorResponse("ID utilisateur invalide");
        }

        $notifications = $this->notificationRepository->findBy([
            'userId' => $userUuid,
            'isRead' => false,
        ]);

        if (empty($notifications)) {
            return $this->responsesService->successResponse([], "Aucune notification non lue");
        }

        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }

        $this->entityHelper->saveMultiple($notifications);

        return $this->responsesService->successResponse([], "Toutes les notifications ont été marquées comme lues");
    }

    #[Route('/{id}', name: 'delete_notification', methods: ['DELETE'])]
    public function deleteNotification(string $id): JsonResponse
    {
        $notification = $this->notificationRepository->find($id);
        if (!$notification) {
            return $this->responsesService->errorResponse("Notification introuvable");
        }

        $this->entityHelper->remove($notification);
        return $this->responsesService->successResponse([], "Notification supprimée");
    }
}
