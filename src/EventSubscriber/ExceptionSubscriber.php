<?php

namespace App\EventSubscriber;

use App\Services\ResponsesService;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\User\UserInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private readonly ResponsesService $responseHelper,
        private readonly Security $security,
    )
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof JWTDecodeFailureException) {
            $message = match ($exception->getReason()) {
                JWTDecodeFailureException::EXPIRED_TOKEN => 'Votre session a expirée, veuillez vous reconnecter.',
                JWTDecodeFailureException::INVALID_TOKEN => 'Votre session est invalide, veuillez vous reconnecter.',
                default => 'Jeton (Token de session) JWT introuvable ou invalide, veuillez vous authentifié.'
            };
        } else {
            $message = $exception->getMessage();
        }

        $content = $this->responseHelper->errorResponse($message, $exception->getCode(), [], 401);
        $event->setResponse($content);
    }
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest()->getUri();
        $method = $event->getRequest()->getRealMethod();
        $routeName = $event->getRequest()->attributes->get('_route');
        $clientIpAddress = $event->getRequest()->getClientIp();
        $user = $this->security->getToken()?->getUser() instanceof UserInterface ? $this->security->getToken()?->getUser()?->getFullName() : 'Anonymous';
//        if ($routeName === "app_auth") return;

        $body = [
            'reqUrl' => $request,
            'reqMethod' => $method,
            'user' => $user,
            'routeName' => $routeName,
            'ipAddress' => $clientIpAddress,
        ];
        $content = $this->responseHelper->successResponse($body, "Action {$body['reqMethod']} sur {$body['reqUrl']} par {$body['user']}.");
        $event->setResponse($content);

    }
    public function onControllerEvent(ControllerEvent $event): void
    {
//         dd($event->getController());
        $request = $event->getRequest()->getUri();
        $method = $event->getRequest()->getRealMethod();
        $routeName = $event->getRequest()->attributes->get('_route');
        $clientIpAddress = $event->getRequest()->getClientIp();
        $user = $this->security->getToken()?->getUser() instanceof UserInterface ? $this->security->getToken()?->getUser()?->getFullName() : 'Anonymous';
        if ($routeName === "app_auth") return;

        $body = [
            'reqUrl' => $request,
            'reqMethod' => $method,
            'user' => $user,
            'routeName' => $routeName,
            'ipAddress' => $clientIpAddress,
        ];

//        dd($body);
//       $content = $this->responseHelper->baseResponse("Action {$body['reqMethod']} sur {$body['reqUrl']} par {$body['user']}.", $body);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
//            KernelEvents::REQUEST => 'onKernelRequest',
//            ControllerEvent::class => 'onControllerEvent',
        ];
    }
}
