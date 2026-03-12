<?php

namespace App\EventSubscriber;


use App\Services\ResponsesService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


final class AuthenticationEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ResponsesService $responseHelper,
    )
    {
    }


    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationFailureEvent::class => 'onAuthenticationFailureResponse',
        ];
    }

    public function onAuthenticationFailureResponse(AuthenticationFailureEvent $event): void
    {
        $content = $this->responseHelper->errorResponse("Mauvaises informations d'identification, veuillez vérifier que votre e-mail/mot de passe sont correctement définis");
        $event->setResponse($content);
    }
}