<?php

namespace App\Controller;

use App\Entity\EmergencyContact;
use App\Repository\EmergencyContactRepository;
use App\Services\ResponsesService;
use App\Services\EntityHelperService;
use App\Services\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/emergency-contact')]
class EmergencyContactController extends AbstractController
{
    public function __construct(
        private readonly EmergencyContactRepository $emergencyContactRepository,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
        private readonly PaginationService $paginationService,
    ) {}

    #[Route('', name: 'get_all_emergency_contacts', methods: ['GET'])]
    public function getAllEmergencyContacts(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        
        $queryBuilder = $this->emergencyContactRepository->createQueryBuilder('ec')
            ->orderBy('ec.isPrimary', 'DESC')
            ->addOrderBy('ec.name', 'ASC');
            
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ['emergency_contact']);
    }

    #[Route('/user/{userId}', name: 'get_emergency_contacts_by_user', methods: ['GET'])]
    public function getEmergencyContactsByUser(string $userId): JsonResponse
    {
        $contacts = $this->emergencyContactRepository->findBy(
            ['userId' => $userId],
            ['isPrimary' => 'DESC', 'name' => 'ASC']
        );
        
        $body = json_decode($this->serializer->serialize($contacts, 'json', ["groups" => "emergency_contact"]), true);
        return $this->responsesService->successResponse($body, "Contacts d'urgence récupérés");
    }

    #[Route('/{id}', name: 'get_emergency_contact_by_id', methods: ['GET'])]
    public function getEmergencyContactById(string $id): JsonResponse
    {
        $contact = $this->emergencyContactRepository->find($id);
        
        if (!$contact) {
            return $this->responsesService->errorResponse("Contact d'urgence introuvable");
        }
        
        $body = json_decode($this->serializer->serialize($contact, 'json', ["groups" => "emergency_contact"]), true);
        return $this->responsesService->successResponse($body, "Contact d'urgence trouvé");
    }

    #[Route('', name: 'create_emergency_contact', methods: ['POST'])]
    public function createEmergencyContact(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || empty($data)) {
            return $this->responsesService->errorResponse("Données invalides");
        }

        $contact = $this->serializer->deserialize(json_encode($data), EmergencyContact::class, 'json');
        $contact = $this->entityHelper->save($contact);

        $body = json_decode($this->serializer->serialize($contact, 'json', ["groups" => "emergency_contact"]), true);
        return $this->responsesService->successResponse($body, "Contact d'urgence créé avec succès");
    }

    #[Route('/{id}', name: 'update_emergency_contact', methods: ['PUT'])]
    public function updateEmergencyContact(string $id, Request $request): JsonResponse
    {
        $contact = $this->emergencyContactRepository->find($id);
        if (!$contact) {
            return $this->responsesService->errorResponse("Contact d'urgence introuvable");
        }

        $data = json_decode($request->getContent(), true);
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($contact, $setter)) {
                $contact->$setter($value);
            }
        }

        $contact = $this->entityHelper->update($contact);
        $body = json_decode($this->serializer->serialize($contact, 'json', ["groups" => "emergency_contact"]), true);
        return $this->responsesService->successResponse($body, "Contact d'urgence mis à jour");
    }

    #[Route('/{id}', name: 'delete_emergency_contact', methods: ['DELETE'])]
    public function deleteEmergencyContact(string $id): JsonResponse
    {
        $contact = $this->emergencyContactRepository->find($id);
        if (!$contact) {
            return $this->responsesService->errorResponse("Contact d'urgence introuvable");
        }

        $this->entityHelper->remove($contact);
        return $this->responsesService->successResponse([], "Contact d'urgence supprimé");
    }
}