<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Entity\Cities;
use App\State\EntityClassDtoStateProcessor;
use App\State\EntityToDtoStateProvider;
use App\Validator\City\UniqueCity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'City',
    description: 'Cities of great people!',
    operations: [
        new Get(),
        new GetCollection(),
        new Post()
    ],
    normalizationContext: ['groups' => ['city:read']],
    denormalizationContext: ['groups' => ['city:write']],
    provider: EntityToDtoStateProvider::class,
    processor: EntityClassDtoStateProcessor::class,
    stateOptions: new Options(entityClass: Cities::class)
)]
class CitiesApi
{
    #[Groups(["city:read"])]
    public ?int $id = null;

    #[Groups(["city:read", "city:write"])]
    #[UniqueCity]
    public ?string $city = null;

    #[Groups(["city:read"])]
    public array $masters = [];
}