<?php

namespace App\EventSubscriber;

use App\Services\ResponsesService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class JWTEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ResponsesService $responseHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            JWTExpiredEvent::class => 'onJWTExpired',
            JWTInvalidEvent::class => 'onJWTInvalid',
            JWTNotFoundEvent::class => 'onJWTNotFound',
        ];
    }

    public function onJWTExpired(JWTExpiredEvent $event): void
    {
        $response = $this->responseHelper->errorResponse(
            "Votre session a expiré, veuillez vous reconnecter.",
            0,
            [],
            401
        );

        $event->setResponse($response);
    }
    public function onJWTInvalid(JWTInvalidEvent $event): void
    {
        $response = $this->responseHelper->errorResponse(
            "Votre session est invalide, veuillez vous reconnecter.",
            0,
            [],
            401
        );

        $event->setResponse($response);
    }

    public function onJWTNotFound(JWTNotFoundEvent $event): void
    {
        $response = $this->responseHelper->errorResponse(
            "Jeton (Token de session) JWT introuvable, veuillez vous authentifié.",
            0,
            [],
            401
        );

        $event->setResponse($response);
    }


}
