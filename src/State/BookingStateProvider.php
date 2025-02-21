<?php

namespace App\State;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\BookingsApi;
use App\Entity\Bookings;
use App\Service\EntityLoaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfonycasts\MicroMapper\MicroMapperInterface;

class BookingStateProvider implements ProviderInterface
{
    private EntityManagerInterface $entityManager;
    private Security $security;
    private MicroMapperInterface $microMapper;
    private EntityLoaderHelper $entityLoaderHelper;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        MicroMapperInterface $microMapper,
        EntityLoaderHelper $entityLoaderHelper
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->microMapper = $microMapper;
        $this->entityLoaderHelper = $entityLoaderHelper;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            return $this->getBookingsCollection();
        }

        if (isset($uriVariables['id'])) {
            return $this->getBookingById($uriVariables['id']);
        }

        return null;
    }

    private function getBookingsCollection(): array
    {
        $bookings = $this->entityManager->getRepository(Bookings::class)->findAll();

        return array_map(
            fn(Bookings $b) => $this->microMapper->map($b, BookingsApi::class),
            $bookings
        );
    }

    private function getBookingById(int $id): ?BookingsApi
    {
        $booking = $this->entityLoaderHelper->load(Bookings::class, $id, 'Booking');

        if (!$booking) {
            throw new NotFoundHttpException("Booking with ID {$id} not found");
        }

        return $this->microMapper->map($booking, BookingsApi::class);
    }
}
