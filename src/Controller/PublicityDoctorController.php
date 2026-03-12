<?php

namespace App\Controller;

use App\Entity\PublicityDoctor;
use App\Entity\Notification;
use App\Repository\PublicityDoctorRepository;
use App\Repository\DoctorRepository;
use App\Services\ResponsesService;
use App\Services\EntityHelperService;
use App\Services\PaginationService;
use App\Services\UploadFileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Utils\NotificationPriority;
use App\Utils\NotificationType;
use Symfony\Component\HttpFoundation\File\File;

#[Route('/publicity-doctor')]
class PublicityDoctorController extends AbstractController
{
    public function __construct(
        private readonly PublicityDoctorRepository $publicityRepo,
        private readonly DoctorRepository $doctorRepository,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly UploadFileService $uploadFileService,
        private readonly EntityHelperService $entityHelper,
        private readonly PaginationService $paginationService,
    ) {}

    #[Route('', name: 'get_all_publicity_doctors', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $qb = $this->publicityRepo->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC');

        return $this->paginationService->paginate($qb, $page, $limit, ['publicity_doctor']);
    }

    #[Route('/last-active', name: 'get_last_entries_active', methods: ['GET'])]
    public function getLastEntrie(): JsonResponse
    {
        $items = $this->publicityRepo->findOneBy([], ['createdAt' => 'DESC']);
        $body = json_decode($this->serializer->serialize($items, 'json', ['groups' => 'publicity_doctor']), true);
        return $this->responsesService->successResponse($body, 'Dernière publicité active');
    }
    
    #[Route('/{id}', name: 'get_publicity_doctor_by_id', methods: ['GET'])]
    public function getById(string $id): JsonResponse
    {
        $item = $this->publicityRepo->find($id);
        if (!$item) {
            return $this->responsesService->errorResponse('Publicité introuvable');
        }

        $body = json_decode($this->serializer->serialize($item, 'json', ['groups' => 'publicity_doctor']), true);
        return $this->responsesService->successResponse($body, 'Publicité trouvée');
    }

    #[Route('', name: 'create_publicity_doctor', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if($file) {
            $data = json_decode($request->request->get('data'), true);
            $data['imageUrl'] = $this->uploadFileService->uploadFile($file, "PublicityImages");

        } else {
            $data = json_decode($request->getContent(), true);
            if (!$data || empty($data)) {
                return $this->responsesService->errorResponse('Données invalides');
            }
        }
        
       

        $pub = $this->serializer->deserialize(json_encode($data), PublicityDoctor::class, 'json');
        $pub = $this->entityHelper->save($pub);
        $this->createNotificationsForDoctors($pub);

        $body = json_decode($this->serializer->serialize($pub, 'json', ['groups' => 'publicity_doctor']), true);
        return $this->responsesService->successResponse($body, 'Publicité créée');
    }

    #[Route('/{id}', name: 'update_publicity_doctor', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $item = $this->publicityRepo->find($id);
        if (!$item) {
            return $this->responsesService->errorResponse('Publicité introuvable');
        }
        $file = $request->files->get('file');
        if($file) {
            $data = json_decode($request->request->get('data'), true);
            $data['imageUrl'] = $this->uploadFileService->uploadFile($file, "PublicityImages");

        } else {
             $data = json_decode($request->getContent(), true);
        }
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($item, $setter)) {
                $item->$setter($value);
            }
        }

        $item = $this->entityHelper->update($item);
        $body = json_decode($this->serializer->serialize($item, 'json', ['groups' => 'publicity_doctor']), true);
        return $this->responsesService->successResponse($body, 'Publicité mise à jour');
    }

    #[Route('/{id}', name: 'delete_publicity_doctor', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $item = $this->publicityRepo->find($id);
        if (!$item) {
            return $this->responsesService->errorResponse('Publicité introuvable');
        }

        $this->entityHelper->remove($item);
        return $this->responsesService->successResponse([], 'Publicité supprimée');
    }

    

    private function createNotificationsForDoctors(PublicityDoctor $pub): void
    {
        $doctors = $this->doctorRepository->createQueryBuilder('d')
            ->getQuery()
            ->getResult();

        if ($doctors === []) {
            return;
        }

        $notifications = [];
        $payload = [
            'publicityId' => $pub->getId()?->toRfc4122(),
            'type' => 'PUBLICITY_DOCTOR',
            'imageUrl' => $pub->getImageUrl(),
            'message' => $pub->getTitle(),
            'title' => 'Nouvelle publicité',
        ];

        foreach ($doctors as $doctor) {
            $userId = $doctor->getUserId();
            if (!$userId) {
                continue;
            }

            $notification = new Notification();
            $notification->setUserId($userId)
                ->setType(NotificationType::PROMOTION)
                ->setTitle('Nouvelle publicité')
                ->setMessage($pub->getTitle() ?? 'Une nouvelle publicité est disponible')
                ->setData($payload)
                ->setPriority(NotificationPriority::HIGH)
                ->setIsRead(false);

            $notifications[] = $notification;
        }

        if ($notifications !== []) {
            $this->entityHelper->saveMultiple($notifications);
        }
    }
}
