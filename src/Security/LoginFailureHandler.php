<?php
namespace App\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $translations = [
            'The presented password is invalid.' => 'Mot de passe incorrect.',
            'Bad credentials.' => 'Identifiants incorrects.',
            'Invalid credentials.' => 'Identifiants invalides.',
            'User not found.' => 'Utilisateur introuvable.',
            'Authentication failed.' => 'Échec authentification.'
        ];

        $data = [
            "status" => 0,
            "message" => $translations[$exception->getMessage()] ?? $exception->getMessage(),
        ];
        return new JsonResponse($data, 401);
    }
}