<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Clients;
use App\Filter\ClientSearchFilter;
use App\State\EntityClassDtoStateProcessor;
use App\State\EntityToDtoStateProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Client',
    description: 'Our dear clients!',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Patch(),
    ],
    normalizationContext: ['groups' => ['client:read']],
    denormalizationContext: ['groups' => ['client:write']],
    provider: EntityToDtoStateProvider::class,
    processor: EntityClassDtoStateProcessor::class,
    stateOptions: new Options(entityClass: Clients::class)
)]
#[ApiFilter(ClientSearchFilter::class)]
class ClientsApi
{
    #[Groups(["client:read"])]
    public ?int $id = null;

    #[Groups(["client:read", "client:write"])]
    #[Assert\NotBlank(message: "Name can't be empty")]
    public ?string $name = null;

    #[Groups(["client:read", "client:write"])]
    #[Assert\NotBlank(message: "Surname can't be empty")]
    public ?string $surname = null;

    #[Groups(["client:read", "client:write"])]
    #[Assert\NotBlank(message: "Email can't be empty")]
    #[Assert\Email(message: "Not a valid email address.")]
    public ?string $email = null;

    #[Groups(["client:read", "client:write"])]
    #[Assert\NotBlank(message: "Phone can't be empty")]
    #[Assert\Regex(pattern: "/^\+?[0-9]{7,15}$/", message: "Invalid phone number.")]
    public ?string $phone = null;

    #[Groups(["client:read", "client:write"])]
    public array $pets = [];

    #[Groups(["client:read"])]
    public array $bookings = [];

}