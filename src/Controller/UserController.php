<?php

namespace App\Controller;

use App\Services\UserService;
use App\Request\UserSearchRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/users')]
final class UserController extends AbstractController
{
    public function __construct (private readonly UserService $userService){}
    
    #[Route('', name: 'app_user_create', methods: "POST")]
    public function addUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->userService->addUser($data);
    }

    #[Route('/search', name: 'app_user_search', methods: "POST")]
    public function searchUsers(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $searchRequest = new UserSearchRequest($data);
        return $this->userService->getAllUsers($searchRequest);
    }

    #[Route('/{id}', name: 'app_user_show', methods: "GET")]
    public function getUserById(string $id): JsonResponse
    {
        return $this->userService->getUserById($id);
    }

    #[Route('/{id}', name: 'app_user_update', methods: "PUT")]
    public function updateUser(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return $this->userService->updateUser($id, $data);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: "DELETE")]
    public function removeUser(string $id): JsonResponse
    {
        return $this->userService->removeUser($id);
    }

    #[Route('/{id}/deactivate', name: 'app_user_deactivate', methods: "PATCH")]
    public function deactivateUser(string $id): JsonResponse
    {
        return $this->userService->deactivateUser($id);
    }

    #[Route('/{id}/activate', name: 'app_user_activate', methods: "PATCH")]
    public function activateUser(string $id): JsonResponse
    {
        return $this->userService->activateUser($id);
    }

    #[Route('/{id}/password', name: 'app_user_update_password', methods: "PATCH")]
    public function updatePassword(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $newPassword = $data['newPassword'] ?? null;
        $oldPassword = $data['currentPassword'] ?? null;
        
        if (!$newPassword ) {
            return new JsonResponse(['message' => 'Mot de passe requis'], 400);
        }
        
        return $this->userService->updatePassword($id, $newPassword, $oldPassword);
    }

    #[Route('/{id}/add-documents', name: 'app_user_add_documents', methods: "POST")]
    public function addDocuments(string $id, Request $request): JsonResponse
    {
        /** @var UploadedFile $file */
        $files = $request->files->get('documents');
        return $this->userService->uploadsUserDocumentFile($id, $files, "UserRegistrationDocuments");
    }

    #[Route('/{id}/profile-image', name: 'app_user_update_profile_image', methods: "POST")]
    public function updateProfileImage(string $id, Request $request): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('profileImage');
       
        return $this->userService->updateUserProfileImage($id, $file);
    }

}
