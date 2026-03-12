<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use App\Services\ResponsesService;
use App\Services\EntityHelperService;
use App\Services\PaginationService;
use App\Services\RatingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Utils\RevieweeType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\Notification;
use App\Repository\DoctorRepository;
use App\Utils\NotificationPriority;
use App\Utils\NotificationType;

#[Route('/review')]
class ReviewController extends AbstractController
{
    public function __construct(
        private readonly ReviewRepository $reviewRepository,
        private readonly DoctorRepository $doctorRepository,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
        private readonly PaginationService $paginationService,
        private readonly RatingService $ratingService,
    ) {}

    #[Route('', name: 'get_all_reviews', methods: ['GET'])]
    public function getAllReviews(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        
        $queryBuilder = $this->reviewRepository->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC');
            
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ['review']);
    }

    #[Route('/entity/{type}/{id}', name: 'get_reviews_by_entity', methods: ['GET'])]
    public function getReviewsByEntity(string $type, string $id): JsonResponse
    {
        try {
            $revieweeType = RevieweeType::from(strtoupper($type));
            $revieweeId = Uuid::fromString($id);
        } catch (\Exception $e) {
            return $this->responsesService->errorResponse("Type ou ID invalide");
        }

        $result = $this->ratingService->getReviewsByRevieweeId($revieweeType, $revieweeId);
        
        $reviews = json_decode($this->serializer->serialize($result['reviews'], 'json', ["groups" => "review"]), true);
        
        $response = [
            'reviews' => $reviews,
            'statistics' => $result['statistics']
        ];
        
        return $this->responsesService->successResponse($response, "Avis et statistiques récupérés");
    }

    #[Route('/{id}', name: 'get_review_by_id', methods: ['GET'])]
    public function getReviewById(string $id): JsonResponse
    {
        $review = $this->reviewRepository->find($id);
        
        if (!$review) {
            return $this->responsesService->errorResponse("Avis introuvable");
        }
        
        $body = json_decode($this->serializer->serialize($review, 'json', ["groups" => "review"]), true);
        return $this->responsesService->successResponse($body, "Avis trouvé");
    }

    #[Route('', name: 'create_review', methods: ['POST'])]
    public function createReview(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || empty($data)) {
            return $this->responsesService->errorResponse("Données invalides");
        }

        $review = $this->serializer->deserialize(json_encode($data), Review::class, 'json');
        $review = $this->entityHelper->save($review);

        // Mettre à jour le rating de l'entité
        $this->ratingService->updateEntityRating($review->getRevieweeType(), $review->getRevieweeId());
        $this->createReviewNotification($review, 'CREATED');

        $body = json_decode($this->serializer->serialize($review, 'json', ["groups" => "review"]), true);
        return $this->responsesService->successResponse($body, "Avis créé avec succès");
    }

    #[Route('/{id}', name: 'update_review', methods: ['PUT'])]
    public function updateReview(string $id, Request $request): JsonResponse
    {
        $review = $this->reviewRepository->find($id);
        if (!$review) {
            return $this->responsesService->errorResponse("Avis introuvable");
        }

        $data = json_decode($request->getContent(), true);
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($review, $setter)) {
                $review->$setter($value);
            }
        }

        $review = $this->entityHelper->update($review);
        
        // Mettre à jour le rating de l'entité
        $this->ratingService->updateEntityRating($review->getRevieweeType(), $review->getRevieweeId());
        $this->createReviewNotification($review, 'UPDATED');
        
        $body = json_decode($this->serializer->serialize($review, 'json', ["groups" => "review"]), true);
        return $this->responsesService->successResponse($body, "Avis mis à jour");
    }

    #[Route('/{id}', name: 'delete_review', methods: ['DELETE'])]
    public function deleteReview(string $id): JsonResponse
    {
        $review = $this->reviewRepository->find($id);
        if (!$review) {
            return $this->responsesService->errorResponse("Avis introuvable");
        }

        $revieweeType = $review->getRevieweeType();
        $revieweeId = $review->getRevieweeId();
        
        $this->entityHelper->remove($review);
        
        // Mettre à jour le rating de l'entité après suppression
        $this->ratingService->updateEntityRating($revieweeType, $revieweeId);
        
        return $this->responsesService->successResponse([], "Avis supprimé");
    }

    #[Route('/{id}/verify', name: 'verify_review', methods: ['POST'])]
    public function verifyReview(string $id, Request $request): JsonResponse
    {
        $review = $this->reviewRepository->find($id);
        if (!$review) {
            return $this->responsesService->errorResponse("Avis introuvable");
        }

        $data = json_decode($request->getContent(), true);
        $review->setIsVerified(true)
            ->setVerifiedBy($data['verifiedBy'] ?? null)
            ->setVerifiedAt(new \DateTimeImmutable());

        $review = $this->entityHelper->update($review);
        $this->createReviewNotification($review, 'VERIFIED');
        $body = json_decode($this->serializer->serialize($review, 'json', ["groups" => "review"]), true);
        return $this->responsesService->successResponse($body, "Avis vérifié");
    }

    private function createReviewNotification(Review $review, string $event): void
    {
        if ($review->getRevieweeType() !== RevieweeType::DOCTOR) {
            return;
        }

        $doctor = $this->doctorRepository->find($review->getRevieweeId());
        if (!$doctor || !$doctor->getUserId()) {
            return;
        }

        $title = match ($event) {
            'CREATED' => 'Nouveau avis reçu',
            'UPDATED' => 'Avis mis à jour',
            'VERIFIED' => 'Avis vérifié',
            default => 'Avis',
        };

        $message = match ($event) {
            'CREATED' => 'Vous avez reçu un nouvel avis.',
            'UPDATED' => 'Un avis vous concernant a été mis à jour.',
            'VERIFIED' => 'Un avis vous concernant a été vérifié.',
            default => 'Notification d\'avis.',
        };

        $notification = new Notification();
        $notification->setUserId($doctor->getUserId())
            ->setType(NotificationType::MESSAGE)
            ->setTitle($title)
            ->setMessage($message)
            ->setData([
                'reviewId' => $review->getId()?->toRfc4122(),
                'revieweeId' => $review->getRevieweeId()?->toRfc4122(),
                'rating' => $review->getRating(),
            ])
            ->setPriority(NotificationPriority::LOW)
            ->setIsRead(false);

        $this->entityHelper->save($notification);
    }
}
