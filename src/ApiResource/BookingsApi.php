<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Bookings;
use App\Filter\TimeBetweenFilter;
use App\State\EntityClassDtoStateProcessor;
use App\State\EntityToDtoStateProvider;
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
    provider: EntityToDtoStateProvider::class,
    processor: EntityClassDtoStateProcessor::class,
    stateOptions: new Options(entityClass: Bookings::class),
)]
#[ApiFilter(DateFilter::class, properties: ['date'])]
#[ApiFilter(TimeBetweenFilter::class)]
#[ApiFilter(NumericFilter::class, properties: ['id_client.id', 'id_master.id'])]
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
    #[FutureDateTime]
    public ?string $date = null;

    #[Groups(["booking:read", "booking:write"])]
    #[BookingAvailability]
    #[FutureDateTime]
    public ?string $timeStart = null;

    #[Groups(["booking:read", "booking:write"])]
    #[BookingAvailability]
    #[FutureDateTime]
    public ?string $timeStop = null;

    #[Groups(["booking:read", "booking:write"])]
    public ?int $petId = null;

    #[Groups(["booking:read", "booking:write"])]
    public ?int $clientId = null;

    #[Groups(["booking:read"])]
    public ?string $status = null;
}