<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Services;
use App\Filter\ServiceSearchFilter;
use App\State\EntityClassDtoStateProcessor;
use App\State\EntityToDtoStateProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Service',
    description: 'Set up your services!',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Patch()
    ],
    normalizationContext: ['groups' => ['services:read']],
    denormalizationContext: ['groups' => ['services:write']],
    provider: EntityToDtoStateProvider::class,
    processor: EntityClassDtoStateProcessor::class,
    stateOptions: new Options(entityClass: Services::class)
)]
#[ApiFilter(ServiceSearchFilter::class)]
#[ApiFilter(RangeFilter::class, properties: ['cost'])]
#[ApiFilter(NumericFilter::class, properties: ['masters.id'])]
class ServicesApi
{
    #[Groups(["services:read"])]
    public ?int $id = null;

    #[Groups(["services:read", "services:write"])]
    #[Assert\NotBlank(message: "The name cannot be blank.")]
    public ?string $name = null;

    #[Groups(["services:read", "services:write"])]
    #[Assert\NotBlank(message: "The cost cannot be blank.")]
    public ?int $cost = null;

    #[Groups(["services:read", "services:write"])]
    #[Assert\NotBlank(message: "The default time cannot be blank.")]
    #[Assert\Regex(pattern: "/^\d{2}:\d{2}:\d{2}$/", message: "The default time must be in the format HH:MM:SS.")]
    public ?string $default_time = null;

    #[Groups(["services:read"])]
    public array $mastersId = [];
}