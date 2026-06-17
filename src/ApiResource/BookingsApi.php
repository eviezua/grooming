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
use App\Controller\CreateBookingWithNewClientController;
use App\Entity\Bookings;
use App\Filter\TimeBetweenFilter;
use App\State\EntityClassDtoStateProcessor;
use App\State\EntityToDtoStateProvider;
use App\Validator\Booking\BookingAvailability;
use App\Validator\FutureDateTime;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Booking',
    description: 'Your bookings are here!',
    operations: [
        new Get(security: "is_granted('BOOKING_VIEW', object)"),
        new GetCollection(security: "is_granted('PUBLIC_ACCESS')"),
        new Post(securityPostDenormalize: "is_granted('BOOKING_CREATE', object)"),
        new Post(
            uriTemplate: '/bookings/with-new-client',
            controller: CreateBookingWithNewClientController::class,
            normalizationContext: ['groups' => ['booking:read']],
            denormalizationContext: ['groups' => ['booking:new-client:write']],
            securityPostDenormalize: "is_granted('BOOKING_CREATE', object)",
            validationContext: ['groups' => ['booking:new-client:write']],
            read: false,
        ),
        new Put(securityPostDenormalize: "is_granted('BOOKING_EDIT', object)"),
        new Patch(inputFormats: ['json' => ['application/merge-patch+json']], securityPostDenormalize: "is_granted('BOOKING_EDIT', object)"),
        new Delete(security: "is_granted('BOOKING_DELETE', object)"),
    ],
    normalizationContext: ['groups' => ['booking:read'], 'enable_max_depth' => true],
    denormalizationContext: ['groups' => ['booking:write']],
    mercure: ['private' => false],
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

    #[Groups(["booking:read", "booking:write", "booking:new-client:write"])]
    public ?int $masterId = null;

    #[Groups(["booking:read", "booking:write", "booking:new-client:write"])]
    public array $services = [];

    #[Groups(["booking:read", "booking:write", "booking:new-client:write"])]
    #[BookingAvailability]
    #[FutureDateTime]
    public ?string $date = null;

    #[Groups(["booking:read", "booking:write", "booking:new-client:write"])]
    #[BookingAvailability]
    #[FutureDateTime]
    public ?string $timeStart = null;

    #[Groups(["booking:read", "booking:write", "booking:new-client:write"])]
    #[BookingAvailability]
    #[FutureDateTime]
    public ?string $timeStop = null;

    #[Groups(["booking:read", "booking:write", "booking:new-client:write"])]
    public ?int $petId = null;

    #[Groups(["booking:read", "booking:write"])]
    public ?int $clientId = null;

    #[Groups(["booking:new-client:write"])]
    #[Assert\NotBlank(message: "Name can't be empty", groups: ['booking:new-client:write'])]
    public ?string $clientName = null;

    #[Groups(["booking:new-client:write"])]
    #[Assert\NotBlank(message: "Surname can't be empty", groups: ['booking:new-client:write'])]
    public ?string $clientSurname = null;

    #[Groups(["booking:new-client:write"])]
    #[Assert\NotBlank(message: "Email can't be empty", groups: ['booking:new-client:write'])]
    #[Assert\Email(message: "Not a valid email address.", groups: ['booking:new-client:write'])]
    public ?string $clientEmail = null;

    #[Groups(["booking:new-client:write"])]
    #[Assert\NotBlank(message: "Phone can't be empty", groups: ['booking:new-client:write'])]
    #[Assert\Regex(pattern: "/^\+?[0-9]{7,15}$/", message: "Invalid phone number.", groups: ['booking:new-client:write'])]
    public ?string $clientPhone = null;

    #[Groups(["booking:read", "booking:write"])]
    public ?string $status = null;

    #[Groups(["booking:read"])]
    public ?float $totalPrice = 0.00;
}