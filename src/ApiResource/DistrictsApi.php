<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Entity\Districts;
use App\Filter\DistrictSearchFilter;
use App\State\EntityClassDtoStateProcessor;
use App\State\EntityToDtoStateProvider;
use App\Validator\District\UniqueDistrict;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'District',
    description: 'Districts of great people!',
    operations: [
        new Get(),
        new GetCollection(),
        new Post()
    ],
    normalizationContext: ['groups' => ['district:read']],
    denormalizationContext: ['groups' => ['district:write']],
    provider: EntityToDtoStateProvider::class,
    processor: EntityClassDtoStateProcessor::class,
    stateOptions: new Options(entityClass: Districts::class)
)]
#[ApiFilter(DistrictSearchFilter::class)]
#[ApiFilter(NumericFilter::class, properties: ['city.id'])]
class DistrictsApi
{
    #[Groups(["district:read"])]
    public ?int $id = null;

    #[Groups(["district:read", "district:write"])]
    #[UniqueDistrict]
    public ?string $name = null;

    #[Groups(["district:read", "district:write"])]
    public ?int $cityId;
}