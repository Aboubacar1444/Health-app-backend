<?php

namespace App\Services;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Uid\Uuid;

class MailerService
{
    public function __construct(private MailerInterface $mailer)
    {

    }

    /**
     * @throws TransportExceptionInterface
     */
    public function ResetPassEmailSender(string $userEmail, $token): void
    {

        $url = $_ENV['APP_URL'] . "api/forgot-password";

        $email = (new TemplatedEmail())
            ->from('noreply@sama.money')
            ->to($userEmail)
            ->priority(Email::PRIORITY_HIGH)
            ->subject('[SAMA MONEY] Rénitialisation de mot de passe')
            ->htmlTemplate("forgotpass.html.twig")
            ->context([
                'resetPassUrl' => sprintf("$url/%s", $token)
            ]);
        // $email->getHeaders()->addTextHeader('Content-Transfer-Encoding', 'base64');
        $this->mailer->send($email);
    }

    public function GeneratePassEmailSender(User $agent, $password): void
    {

        $email = (new TemplatedEmail())
            ->from('noreply@sama.money')
            ->to($agent->getEmail())
            ->priority(Email::PRIORITY_HIGH)
            ->subject('[SAMA MONEY] Activation de compte')
            ->htmlTemplate("generatepass.html.twig")
            ->context([
                'Agent' => $agent->getFullName(),
                'generatePassword' => $password,
            ]);
        // $email->getHeaders()->addTextHeader('Content-Transfer-Encoding', 'base64');
        $this->mailer->send($email);
    }

    public function GeneratePinEmailSender (User $user, int $pin, \DateTimeImmutable $expiredTime) : void
    {
        $email = (new TemplatedEmail())
            ->from('noreply@sama.money')
            ->to($user->getEmail())
            ->priority(Email::PRIORITY_HIGH)
            ->subject("[SAMA MONEY] Code PIN d'authentification")
            ->htmlTemplate("generatepin.html.twig")
            ->context([
                'Agent' => $user->getFullName(),
                'codePin' => $pin,
                'sessionTime'=> $expiredTime
            ]);
        // $email->getHeaders()->addTextHeader('Content-Transfer-Encoding', 'base64');
        $this->mailer->send($email);
    }
}
