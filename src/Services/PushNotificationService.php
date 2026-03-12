<?php

namespace App\Services;

use App\Entity\DevicePushToken;
use App\Entity\Notification;
use App\Repository\DevicePushTokenRepository;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class PushNotificationService
{
    private const ALLOWED_PLATFORMS = ['ios', 'android', 'web'];

    public function __construct(
        private readonly DevicePushTokenRepository $devicePushTokenRepository,
        private readonly UserRepository $userRepository,
        private readonly ResponsesService $responsesService,
        private readonly EntityHelperService $entityHelper,
        private readonly SerializerInterface $serializer,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $expoPushUrl = 'https://exp.host/--/api/v2/push/send',
        private readonly string $expoAccessToken = 'Iu51J-6ys0TCZGANr84dKwMmAWfuYty2x_jxbdjy',
    ) {}

    public function registerDeviceToken(array $data): JsonResponse
    {
        $userId = trim((string) ($data['userId'] ?? ''));
        $tokenValue = trim((string) ($data['token'] ?? ''));
        $platform = strtolower(trim((string) ($data['platform'] ?? '')));
        $deviceName = isset($data['deviceName']) ? trim((string) $data['deviceName']) : null;

        if ($userId === '' || $tokenValue === '' || $platform === '') {
            return $this->responsesService->errorResponse('userId, token et platform sont requis');
        }

        if (!in_array($platform, self::ALLOWED_PLATFORMS, true)) {
            return $this->responsesService->errorResponse('platform invalide. Valeurs autorisees: ios, android, web');
        }

        try {
            $userUuid = Uuid::fromString($userId);
        } catch (\Throwable) {
            return $this->responsesService->errorResponse('userId invalide');
        }

        if (!$this->userRepository->find($userUuid)) {
            return $this->responsesService->errorResponse('Utilisateur introuvable');
        }

        $token = $this->devicePushTokenRepository->findOneBy(['token' => $tokenValue]) ?? new DevicePushToken();

        $token->setUserId($userUuid)
            ->setToken($tokenValue)
            ->setPlatform($platform)
            ->setProvider('expo')
            ->setDeviceName($deviceName ?: null)
            ->setIsActive(true)
            ->setLastUsedAt(new \DateTimeImmutable());

        if ($token->getId()) {
            $token = $this->entityHelper->update($token);
        } else {
            $token = $this->entityHelper->save($token);
        }

        $body = json_decode($this->serializer->serialize($token, 'json', ['groups' => 'device_push_token']), true);

        return $this->responsesService->successResponse($body, 'Token device enregistre');
    }

    public function removeDeviceToken(array $data): JsonResponse
    {
        $userId = trim((string) ($data['userId'] ?? ''));
        $tokenValue = trim((string) ($data['token'] ?? ''));

        if ($userId === '' || $tokenValue === '') {
            return $this->responsesService->errorResponse('userId et token sont requis');
        }

        try {
            $userUuid = Uuid::fromString($userId);
        } catch (\Throwable) {
            return $this->responsesService->errorResponse('userId invalide');
        }

        $token = $this->devicePushTokenRepository->findOneBy([
            'userId' => $userUuid,
            'token' => $tokenValue,
            'isActive' => true,
        ]);

        if (!$token) {
            return $this->responsesService->successResponse([], 'Token deja supprime ou introuvable');
        }

        $token->setIsActive(false);
        $this->entityHelper->update($token);

        return $this->responsesService->successResponse([], 'Token supprime');
    }

    public function dispatchNotification(Notification $notification): void
    {
        $userId = $notification->getUserId();
        if (!$userId) {
            return;
        }

        $tokens = $this->devicePushTokenRepository->findActiveTokensByUserId($userId);
        if ($tokens === []) {
            return;
        }

        foreach ($tokens as $token) {
            $providerData = $this->sendViaExpo($token, $notification);
            if (($providerData['status'] ?? null) === 'ok') {
                $token->setLastUsedAt(new \DateTimeImmutable());
            }
        }

        $this->entityHelper->saveMultiple($tokens);
    }

    private function sendViaExpo(DevicePushToken $token, Notification $notification): array
    {
        $payload = [
            'to' => $token->getToken(),
            'title' => $notification->getTitle(),
            'body' => $notification->getMessage(),
            'data' => $notification->getData() ?? [],
            'sound' => 'default',
        ];

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($this->expoAccessToken !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->expoAccessToken;
        }

        try {
            $response = $this->httpClient->request('POST', $this->expoPushUrl, [
                'headers' => $headers,
                'json' => $payload,
            ]);

            $responseBody = $response->toArray(false);
            $data = $responseBody['data'] ?? null;

            if (!is_array($data)) {
                $this->logger->warning('Reponse Expo inattendue', ['response' => $responseBody]);
                return [];
            }

            if (($data['status'] ?? null) === 'error') {
                $errorCode = $data['details']['error'] ?? null;
                if ($errorCode === 'DeviceNotRegistered') {
                    $token->setIsActive(false);
                }

                $this->logger->warning('Erreur Expo Push', [
                    'tokenId' => $token->getId()?->toRfc4122(),
                    'error' => $data,
                ]);
            }

            return $data;
        } catch (\Throwable $e) {
            $this->logger->error('Echec envoi push Expo', [
                'tokenId' => $token->getId()?->toRfc4122(),
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
