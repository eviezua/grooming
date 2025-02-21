<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\State\BookingStateProcessor;
use App\State\BookingStateProvider;
use App\Validator\Booking\BookingAvailability;
use App\Validator\FutureDateTime;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'Booking',
    description: 'Your bookings are here!',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Patch(inputFormats: ['json' => ['application/merge-patch+json']]),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['booking:read'], 'enable_max_depth' => true],
    denormalizationContext: ['groups' => ['booking:write']],
    provider: BookingStateProvider::class,
    processor: BookingStateProcessor::class,
)]
class BookingsApi
{
    #[Groups(["booking:read"])]
    public ?int $id = null;

    #[Groups(["booking:read", "booking:write"])]
    public ?int $masterId = null;

    #[Groups(["booking:read", "booking:write"])]
    public array $services = [];

    #[Groups(["booking:read", "booking:write"])]
    #[BookingAvailability]
    public ?\DateTimeInterface $date = null;

    #[Groups(["booking:read", "booking:write"])]
    #[BookingAvailability]
    #[FutureDateTime]
    public ?\DateTimeInterface $timeStart = null;

    #[Groups(["booking:read", "booking:write"])]
    #[BookingAvailability]
    public ?\DateTimeInterface $timeStop = null;

    #[Groups(["booking:read", "booking:write"])]
    public ?int $petId = null;

    #[Groups(["booking:read", "booking:write"])]
    public ?int $clientId = null;

}