<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Masters;
use App\Filter\MasterAvailableTimeFilter;
use App\Filter\MasterSearchFilter;
use App\State\EntityClassDtoStateProcessor;
use App\State\EntityToDtoStateProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Master',
    description: 'Our Masters.',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Patch(inputFormats: ['json' => ['application/merge-patch+json']])
    ],
    normalizationContext: ['groups' => ['master:read'], 'enable_max_depth' => true],
    denormalizationContext: ['groups' => ['master:write']],
    provider: EntityToDtoStateProvider::class,
    processor: EntityClassDtoStateProcessor::class,
    stateOptions: new Options(entityClass: Masters::class)
)]
#[ApiFilter(MasterSearchFilter::class)]
#[ApiFilter(MasterAvailableTimeFilter::class)]
#[ApiFilter(NumericFilter::class, properties: ['id_city.id', 'id_services.id', 'id_pets.id',])]
class MastersApi
{
    #[Groups(["master:read"])]
    public ?int $id = null;

    #[Groups(["master:read", "master:write"])]
    #[Assert\NotBlank(message: "Name can't be empty")]
    public ?string $name = null;

    #[Groups(["master:read", "master:write"])]
    #[Assert\NotBlank(message: "Surname can't be empty")]
    public ?string $surname = null;

    #[Groups(["master:read", "master:write"])]
    public array $servicesId = [];

    #[Groups(["master:read", "master:write"])]
    public ?int $cityId = null;

    #[Groups(["master:read", "master:write"])]
    public array $petsId = [];

    #[Groups(["master:write"])]
    #[Assert\Length(min: 6, max: 100, minMessage: "Password must be at least {{ limit }} characters.")]
    public ?string $password = null;

    #[Groups(["master:read", "master:write"])]
    #[Assert\NotBlank(message: "Email can't be empty")]
    #[Assert\Email(message: "Not a valid email address.")]
    public ?string $email = null;

    #[Groups(["master:read", "master:write"])]
    #[Assert\Regex(pattern: "/^\+?[0-9]{7,15}$/", message: "Invalid phone number.")]
    public ?string $phone = null;

    #[Groups(["master:read", "master:write"])]
    public ?string $photo = null;

    #[Groups(["master:read"])]
    public array $schedulesId = [];

    #[Groups(["master:read"])]
    public array $bookingsId = [];

}