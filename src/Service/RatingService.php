<?php

namespace App\Service;

use App\Entity\Review;
use App\Repository\BookingsRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;

class RatingService
{
    public function __construct(
        private BookingsRepository $bookingsRepository,
        private EntityManagerInterface $em,
        private ReviewRepository $reviewRepository,
    ) {}

    public function addReviewFromTelegram(int $bookingId, int $score): void
    {
        $booking = $this->bookingsRepository->find($bookingId);
        if (!$booking) return;

        $master = $booking->getIdMaster();

        $review = new Review();
        $review->setBooking($booking);
        $review->setMaster($master);
        $review->setRating($score);

        $this->em->persist($review);
        $this->em->flush();

        $newAverage = $this->reviewRepository->updateMasterRating($master);
        $master->setAvgRating($newAverage);

        $this->em->flush();
    }
}