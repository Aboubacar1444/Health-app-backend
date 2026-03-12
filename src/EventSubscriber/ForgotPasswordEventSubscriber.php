<?php
namespace App\EventSubscriber ;

// ...

use App\Repository\UserRepository;
use App\Services\MailerService;
use CoopTilleuls\ForgotPasswordBundle\Event\CreateTokenEvent;
use CoopTilleuls\ForgotPasswordBundle\Event\UpdatePasswordEvent;
use CoopTilleuls\ForgotPasswordBundle\Event\UserNotFoundEvent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;


use Twig\Environment;

final class ForgotPasswordEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage, 
        private readonly MailerInterface $mailer, 
        private readonly Environment $twig,
        private readonly UserRepository $userManager,
        private UserPasswordHasherInterface $hasher,
        private MailerService $mailerService,
        ){}
    
    public static function getSubscribedEvents(): array
    {
        return [
            // Symfony 4.3 and inferior, use 'kernel.request' event name
            KernelEvents::REQUEST => 'onKernelRequest',
            CreateTokenEvent::class => 'onCreateToken',
            UpdatePasswordEvent::class => 'onUpdatePassword',
            UserNotFoundEvent::class => 'onUserNotFound',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !str_starts_with($event->getRequest()->attributes->get('_route'), 'forgot_password')) {
            return;
        }

        // User should not be authenticated on forgot password
        $token = $this->tokenStorage->getToken();
        if (null !== $token && $token->getUser() instanceof UserInterface) {
            throw new AccessDeniedHttpException("Accès interdit");
        }
    }

    public function onCreateToken(CreateTokenEvent $event): void
    {
        $passwordToken = $event->getPasswordToken();
        $user = $passwordToken->getUser();
        $this->mailerService->ResetPassEmailSender($user->getEmail(), $passwordToken->getToken());          
    }

    public function onUpdatePassword(UpdatePasswordEvent $event): void 
    {
        $passwordToken = $event->getPasswordToken();
        $user = $passwordToken->getUser();
        $passwordEncoded = $this->hasher->hashPassword($user, $event->getPassword());
        $this->userManager->upgradePassword($user, $passwordEncoded);
    }
    
    public function onUserNotFound(UserNotFoundEvent $event): void
    {
         $context = $event->getContext();
        new JsonResponse($context);
    }

}