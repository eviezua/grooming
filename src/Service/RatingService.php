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
    /**
     * @TODO: It`s working, but should be in repository instead
     */

    public function findLastReviewWithoutComment(string $chatId): ?Review
    {
        return $this->em->getRepository(Review::class)->createQueryBuilder('r')
            ->join('r.booking', 'b')
            ->join('b.id_client', 'c')
            ->where('c.telegramChatId = :chatId')
            ->andWhere('r.comment IS NULL')
            ->setParameter('chatId', $chatId)
            ->orderBy('r.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    /**
     * @TODO: The same as above. Risky code.
     */

    public function saveReview(Review $review): void
    {
        $this->em->persist($review);
        $this->em->flush();
    }
}