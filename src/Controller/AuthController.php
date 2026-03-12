<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\EntityHelperService;
use App\Services\ResponsesService;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/auth')]
final class AuthController extends AbstractController
{
    public function __construct (
        private JWTEncoderInterface $jwtEncoder,
        private readonly ResponsesService $responsesService,
        private readonly EntityHelperService $entityHelper,
        private readonly UserRepository $userRepository,
        private readonly SerializerInterface $serializer,
    ){}
    #[Route('', name: 'app_auth', methods: "POST")]
    public function authentication(#[CurrentUser()] User $user): JsonResponse
    {

        if ($user->isActivated() === false) {
            return $this->responsesService->errorResponse("Votre compte est en cours de validation, veuillez patientez le temps du traitement, vous recevrez un mail une fois terminée.");
        }
        if ($user->isActivated() === null) {
            return $this->responsesService->errorResponse("Votre compte a été désactivé.");
        }

        $user->setLastLoginAt(new \DateTimeImmutable());
        $this->entityHelper->update($user);
        // Generate JWT token
        $expiredTime = time() + 604800; // 7 days;
//        $expiredTime = time() + 2519200; // 28 days;
        $generatedToken = $this->jwtEncoder->encode([
            'username'=>$user->getUserIdentifier(),
            'exp' => $expiredTime,
        ]);
        $user = json_decode($this->serializer->serialize($user, 'json', ["groups" => "user"]) );
        $responseBody = [
            'user' => $user,
            'token' => $generatedToken,
            'expiredAt' => date('d-m-Y H:i:s', $expiredTime),
        ];

        return $this->responsesService->successResponse($responseBody, 'Authentification réussie. Bienvenue '.$user->fullName.'!');
        
    }

    #[Route('/me/{token}', name: 'app_get_user_by_token', methods: "GET")]
    public function getUserByToken($token): JsonResponse
    {
        $data = $this->jwtEncoder->decode($token);
        if (!$data || !isset($data['username'])) {
            return $this->responsesService->errorResponse("Token invalide");
        }
        
        $user = $this->userRepository->findOneBy(['userIdentifier' => $data['username']]);
        
        if (!$user) {
            return $this->responsesService->errorResponse("Utilisateur non trouvé");
        }
        $user = json_decode($this->serializer->serialize($user, 'json', ["groups" => "user"]) );
        return $this->responsesService->successResponse($user, "Détails de l'utilisateur");
    }

    
}
