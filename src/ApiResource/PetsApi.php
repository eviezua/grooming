<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Entity\Pets;
use App\State\EntityClassDtoStateProcessor;
use App\State\EntityToDtoStateProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Pet',
    description: 'Our little friends!',
    operations: [
        new Get(),
        new GetCollection(),
        new Post()
    ],
    normalizationContext: ['groups' => ['pets:read']],
    denormalizationContext: ['groups' => ['pets:write']],
    provider: EntityToDtoStateProvider::class,
    processor: EntityClassDtoStateProcessor::class,
    stateOptions: new Options(entityClass: Pets::class)
)]
class PetsApi
{
    #[Groups(["pets:read"])]
    public ?int $id = null;

    #[Groups(["pets:read", "pets:write"])]
    #[Assert\NotBlank(message: "Spice of pets must not be empty.")]
    public ?string $spice = null;

    #[Groups(["pets:write"])]
    #[Assert\NotBlank(message: "Hair of pets must not be empty.")]
    public ?string $hair = null;

    #[Groups(["pets:read", "pets:write"])]
    #[Assert\NotBlank(message: "Breed of pets must not be empty.")]
    public ?string $breed = null;

    #[Groups(["pets:write"])]
    #[Assert\NotBlank(message: "Size of pets must not be empty.")]
    public ?string $size = null;

    #[Groups(["pets:read"])]
    public ?float $cost_coficient = null;

    #[Groups(["pets:read"])]
    public array $mastersId = [];
}