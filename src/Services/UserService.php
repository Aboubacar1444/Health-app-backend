<?php
namespace App\Services;
use App\Entity\User;
use App\Entity\Notification;
use App\Repository\UserRepository;
use App\Utils\Roles;
use App\Utils\NotificationPriority;
use App\Utils\NotificationType;
use App\Request\UserSearchRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

final class UserService
{

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ResponsesService $responsesService,
        private readonly UploadFileService $uploadFileService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
        private readonly PaginationService $paginationService,

    ) {}

    public function defineUserRole(string $role): ?string
    {
        return match ($role) {
            "ADMIN" => Roles::ROLE_ADMIN->value,
            "GESTION" => Roles::ROLE_GESTION->value,
            "BACKOFFICE" => Roles::ROLE_BACKOFFICE->value,
            
            "AUTRE" => Roles::ROLE_USER->value,
            "MEDECIN" => Roles::ROLE_MEDECIN->value,
            "ETUDIANT" => Roles::ROLE_ETUDIANT->value,
            "PHARMACIEN" => Roles::ROLE_PHARMACIEN->value,
            default => Roles::ROLE_USER->value,
        };

    }

    public function addUser(?array $data): JsonResponse {
        /** @var User $user */
        if (!$data || empty($data)) {
            return $this->responsesService->errorResponse("Données invalides");
        }
        $user = $this->serializer->deserialize(json_encode($data), User::class, 'json');
        $hashedPass = $this->userRepository->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPass);

        if ($this->userRepository->findOneBy(['email' => $user->getEmail()]) || $this->userRepository->findOneBy(['phone' => $user->getPhone()])) {
            return $this->responsesService->errorResponse("Email ou téléphone déjà utilisé");
        }

        if ($user->getUserJob() !== "MEDECIN") {
           $user->setIsActivated(true);
           $message = "Compte créé avec succès. Bienvenue!";
        } else {
            $user->setIsActivated(false);
            $message = "Compte créé avec succès, en attente d'activation par un administrateur.";
        }
        $user->setRoles([$this->defineUserRole($user->getUserJob())]);
        $user = $this->entityHelper->save($user);

        $body = json_decode($this->serializer->serialize($user, 'json', ["groups" => "user"]), true);
        return $this->responsesService->successResponse($body, $message);

    }

    public function updateUser(string $id, array $data): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->responsesService->errorResponse("Utilisateur introuvable");
        }

        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($user, $setter) && $key !== 'password') {
                $user->$setter($value);
            }
        }

        $user = $this->entityHelper->update($user);
        $body = json_decode($this->serializer->serialize($user, 'json', ["groups" => "user"]), true);
        return $this->responsesService->successResponse($body, "Utilisateur mis à jour avec succès.");
    }

    public function removeUser(string $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->responsesService->errorResponse("Utilisateur introuvable");
        }

        $this->entityHelper->remove($user);
        return $this->responsesService->successResponse([], "Utilisateur supprimé");
    }

    public function deactivateUser(string $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->responsesService->errorResponse("Utilisateur introuvable");
        }

        $user->setIsActivated(null);
        $user = $this->entityHelper->update($user);
        $body = json_decode($this->serializer->serialize($user, 'json', ["groups" => "user"]), true);
        return $this->responsesService->successResponse($body, "Utilisateur désactivé");
    }

    public function activateUser(string $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->responsesService->errorResponse("Utilisateur introuvable");
        }

        $wasActivated = (bool) $user->isActivated();
        $user->setIsActivated(true);
        $user = $this->entityHelper->update($user);
        if (!$wasActivated) {
            $this->createDoctorAccountActivationNotification($user);
        }
        $body = json_decode($this->serializer->serialize($user, 'json', ["groups" => "user"]), true);
        return $this->responsesService->successResponse($body, "Utilisateur activé");
    }

    public function updatePassword(string $id, string $newPassword, string $actualPassword): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->responsesService->errorResponse("Utilisateur introuvable");
        }
        if ($actualPassword) {
            if (!$this->userRepository->isPasswordValid($user, $actualPassword)) {
                return $this->responsesService->errorResponse("Mot de passe actuel incorrect.");
            }
        }
        $hashedPassword = $this->userRepository->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $user = $this->entityHelper->update($user);
        
        return $this->responsesService->successResponse([], "Mot de passe mis à jour");
    }

    public function getAllUsers(?UserSearchRequest $searchRequest = null): JsonResponse
    {
        $queryBuilder = $this->userRepository->createQueryBuilder('u');
        
        if ($searchRequest) {
            $this->applyUserFilters($queryBuilder, $searchRequest);
            return $this->paginationService->paginate($queryBuilder, $searchRequest->page, $searchRequest->limit, ["user"]);
        }
        
        return $this->paginationService->paginate($queryBuilder, 1, 10, ["user"]);
    }

    private function applyUserFilters($queryBuilder, UserSearchRequest $searchRequest): void
    {
        if ($searchRequest->fullName) {
            $queryBuilder->andWhere('u.fullName LIKE :fullName')
                ->setParameter('fullName', '%' . $searchRequest->fullName . '%');
        }
        
        if ($searchRequest->email) {
            $queryBuilder->andWhere('u.email LIKE :email')
                ->setParameter('email', '%' . $searchRequest->email . '%');
        }
        
        if ($searchRequest->phone) {
            $queryBuilder->andWhere('u.phone LIKE :phone')
                ->setParameter('phone', '%' . $searchRequest->phone . '%');
        }
        
        if ($searchRequest->userJob) {
            $queryBuilder->andWhere('u.userJob = :userJob')
                ->setParameter('userJob', $searchRequest->userJob);
        }
        
        if ($searchRequest->isActivated !== null) {
            $queryBuilder->andWhere('u.isActivated = :isActivated')
                ->setParameter('isActivated', $searchRequest->isActivated);
        }
        
        if ($searchRequest->isDocVerified !== null) {
            $queryBuilder->andWhere('u.isDocVerified = :isDocVerified')
                ->setParameter('isDocVerified', $searchRequest->isDocVerified);
        }
        
        if ($searchRequest->isAccountVerified !== null) {
            $queryBuilder->andWhere('u.isAccountVerified = :isAccountVerified')
                ->setParameter('isAccountVerified', $searchRequest->isAccountVerified);
        }
        
        if ($searchRequest->search) {
            $queryBuilder->andWhere('u.fullName LIKE :search OR u.email LIKE :search OR u.phone LIKE :search')
                ->setParameter('search', '%' . $searchRequest->search . '%');
        }
    }

    public function getUserById(string $id): JsonResponse {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->responsesService->errorResponse("User not found");
        }
        $body = json_decode($this->serializer->serialize($user, 'json', ["groups" => "user"]), true);
        return $this->responsesService->successResponse($body, "User fetched successfully");
    }

    public function uploadsUserDocumentFile($userId, $files, string $mediaType): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return $this->responsesService->errorResponse("Aucun utilisateur trouvé pour l'ID fourni.");
        }
        if (!$files) {
            return $this->responsesService->errorResponse("Aucun fichier reçu");
        }
        // assume $files is an array of UploadedFile objects
        if(is_array($files)){
            $fileName = [];
            foreach ($files as $file) {
                try {
                    $fileName [] = $this->uploadFileService->uploadFile($file, $mediaType);
                } catch (\Exception $e) {
                    return $this->responsesService->errorResponse("Erreur lors de l'upload du fichier: " . $e->getMessage());
                }
            }
            if (!empty($user->getDocuments())) {
                $user->setDocuments(array_merge($user->getDocuments(), ["url" => $fileName]));
            } else {
                $user->setDocuments(["url" => $fileName]);
            }
        }
        
       
        
        $this->entityHelper->save($user);
        
        return $this->responsesService->successResponse($user->getDocuments(), "Document(s) uploadé(s) avec succès!");
    }

    public function updateUserProfileImage(string $userId, $file): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return $this->responsesService->errorResponse("Utilisateur introuvable");
        }
        
        if (!$file) {
            return $this->responsesService->errorResponse("Aucun fichier reçu");
        }
        
        try {
            // Supprimer l'ancienne image si elle existe
            if ($user->getProfileImage()) {
                $this->uploadFileService->deleteFile($user->getProfileImage(), "UserImages");
            }
            
            $fileName = $this->uploadFileService->uploadFile($file, "UserImages");
            $user->setProfileImage($fileName);
            $user = $this->entityHelper->update($user);
            
            $body = json_decode($this->serializer->serialize($user, 'json', ["groups" => "user"]), true);
            return $this->responsesService->successResponse($body, "Image de profil mise à jour avec succès");
        } catch (\Exception $e) {
            return $this->responsesService->errorResponse("Erreur lors de l'upload de l'image: " . $e->getMessage());
        }
    }

    private function createDoctorAccountActivationNotification(User $user): void
    {
        if ($user->getUserJob() !== 'MEDECIN') {
            return;
        }

        $notification = new Notification();
        $notification->setUserId($user->getId())
            ->setType(NotificationType::SYSTEM)
            ->setTitle('Compte activé')
            ->setMessage('Votre compte médecin a été activé. Vous pouvez maintenant accéder à votre espace.')
            ->setData([
                'userId' => $user->getId()?->toRfc4122(),
            ])
            ->setPriority(NotificationPriority::NORMAL)
            ->setIsRead(false);

        $this->entityHelper->save($notification);
    }

}
