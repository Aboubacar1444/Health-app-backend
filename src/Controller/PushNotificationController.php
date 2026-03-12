<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Services\EntityHelperService;
use App\Services\PushNotificationService;
use App\Services\ResponsesService;
use App\Utils\NotificationPriority;
use App\Utils\NotificationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

#[Route('/notifications/push-app')]
class PushNotificationController extends AbstractController
{
    public function __construct(
        private readonly PushNotificationService $pushNotificationService,
        private readonly ResponsesService $responsesService,
        private readonly EntityHelperService $entityHelper,
        private readonly SerializerInterface $serializer,
    ) {}

    #[Route('/device-token', name: 'register_device_push_token', methods: ['POST'])]
    public function registerDeviceToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->responsesService->errorResponse('Données invalides');
        }

        return $this->pushNotificationService->registerDeviceToken($data);
    }

    #[Route('/device-token', name: 'delete_device_push_token', methods: ['DELETE'])]
    public function deleteDeviceToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->responsesService->errorResponse('Données invalides');
        }

        return $this->pushNotificationService->removeDeviceToken($data);
    }

    #[Route('/push/send', name: 'send_push_notification', methods: ['POST'])]
    public function sendPush(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->responsesService->errorResponse('Données invalides');
        }

        $userId = trim((string) ($data['userId'] ?? ''));
        $title = trim((string) ($data['title'] ?? ''));
        $body = trim((string) ($data['body'] ?? ''));
        $notificationData = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];
        $typeInput = strtoupper((string) ($data['type'] ?? $notificationData['type'] ?? NotificationType::SYSTEM->value));
        $priorityInput = strtoupper((string) ($data['priority'] ?? NotificationPriority::NORMAL->value));

        if ($userId === '' || $title === '' || $body === '') {
            return $this->responsesService->errorResponse('userId, title et body sont requis');
        }

        try {
            $userUuid = Uuid::fromString($userId);
        } catch (\Throwable) {
            return $this->responsesService->errorResponse('userId invalide');
        }

        try {
            $notificationType = NotificationType::from($typeInput);
        } catch (\Throwable) {
            $notificationType = NotificationType::SYSTEM;
        }

        try {
            $priority = NotificationPriority::from($priorityInput);
        } catch (\Throwable) {
            $priority = NotificationPriority::NORMAL;
        }

        $notification = new Notification();
        $notification->setUserId($userUuid)
            ->setType($notificationType)
            ->setTitle($title)
            ->setMessage($body)
            ->setData($notificationData)
            ->setPriority($priority)
            ->setIsRead(false);

        $notification = $this->entityHelper->save($notification);
        $responseBody = json_decode($this->serializer->serialize($notification, 'json', ['groups' => 'notification']), true);

        return $this->responsesService->successResponse($responseBody, 'Push enregistree et envoyee');
    }
}
