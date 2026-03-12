<?php

namespace App\Services;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use App\Repository\DoctorRepository;
use App\Repository\EstablishmentRepository;
use App\Repository\PharmacyRepository;
use App\Utils\RevieweeType;
use Symfony\Component\Uid\Uuid;

final class RatingService
{
    public function __construct(
        private readonly ReviewRepository $reviewRepository,
        private readonly DoctorRepository $doctorRepository,
        private readonly EstablishmentRepository $establishmentRepository,
        private readonly PharmacyRepository $pharmacyRepository,
        private readonly EntityHelperService $entityHelper,
    ) {}

    public function updateEntityRating(RevieweeType $revieweeType, Uuid $revieweeId): void
    {
        $reviews = $this->reviewRepository->findBy([
            'revieweeType' => $revieweeType,
            'revieweeId' => $revieweeId
        ]);

        if (empty($reviews)) {
            return;
        }

        $totalRating = 0;
        $totalReviews = count($reviews);

        foreach ($reviews as $review) {
            $totalRating += $review->getRating();
        }

        $averageRating = round($totalRating / $totalReviews, 1);

        $entity = $this->getEntity($revieweeType, $revieweeId);
        if ($entity) {
            $entity->setRating((string)$averageRating);
            
            if (method_exists($entity, 'setTotalReviews')) {
                $entity->setTotalReviews($totalReviews);
            }
            
            $this->entityHelper->update($entity);
        }
    }

    public function getReviewsByRevieweeId(RevieweeType $revieweeType, Uuid $revieweeId): array
    {
        $reviews = $this->reviewRepository->findBy(
            [
                'revieweeType' => $revieweeType,
                'revieweeId' => $revieweeId
            ],
            ['createdAt' => 'DESC']
        );

        $totalRating = 0;
        $totalReviews = count($reviews);
        $ratingDistribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        foreach ($reviews as $review) {
            $totalRating += $review->getRating();
            $ratingDistribution[$review->getRating()]++;
        }

        $averageRating = $totalReviews > 0 ? round($totalRating / $totalReviews, 1) : 0;

        return [
            'reviews' => $reviews,
            'statistics' => [
                'totalReviews' => $totalReviews,
                'averageRating' => $averageRating,
                'ratingDistribution' => $ratingDistribution
            ]
        ];
    }

    public function recalculateAllRatings(): void
    {
        // Recalculer pour tous les médecins
        $doctors = $this->doctorRepository->findAll();
        foreach ($doctors as $doctor) {
            $this->updateEntityRating(RevieweeType::DOCTOR, $doctor->getId());
        }

        // Recalculer pour tous les établissements
        $establishments = $this->establishmentRepository->findAll();
        foreach ($establishments as $establishment) {
            $this->updateEntityRating(RevieweeType::ESTABLISHMENT, $establishment->getId());
        }
    }

    private function getEntity(RevieweeType $revieweeType, Uuid $revieweeId): mixed
    {
        return match($revieweeType) {
            RevieweeType::DOCTOR => $this->doctorRepository->find($revieweeId),
            RevieweeType::ESTABLISHMENT => $this->establishmentRepository->find($revieweeId),
        };
    }
}