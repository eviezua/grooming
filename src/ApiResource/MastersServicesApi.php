<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\MastersServices;
use App\State\EntityClassDtoStateProcessor;
use App\State\EntityToDtoStateProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'MastersServices',
    description: 'Services and prices of great people!',
    operations: [
        new Get(security: "is_granted('PUBLIC_ACCESS')"),
        new GetCollection(security: "is_granted('PUBLIC_ACCESS')"),
        new Post(securityPostDenormalize: "is_granted('MS_CREATE', object)"),
        new Patch(inputFormats: ['json' => ['application/merge-patch+json']], securityPostDenormalize: "is_granted('MS_EDIT', object)"),
        new Delete(security: "is_granted('MS_DELETE', object)")
    ],
    normalizationContext: ['groups' => ['ms:read']],
    denormalizationContext: ['groups' => ['ms:write']],
    mercure: ['private' => false],
    provider: EntityToDtoStateProvider::class,
    processor: EntityClassDtoStateProcessor::class,
    stateOptions: new Options(entityClass: MastersServices::class)
)]
#[ApiFilter(OrderFilter::class, properties: ['price'])]
#[ApiFilter(RangeFilter::class, properties: ['price'])]
#[ApiFilter(NumericFilter::class, properties: ['master.id', 'service.id'])]
class MastersServicesApi
{
    #[Groups(["ms:read"])]
    public ?int $id = null;

    #[Groups(["ms:read", "ms:write"])]
    public ?int $masterId = null;

    #[Groups(["ms:read", "ms:write"])]
    public ?int $serviceId = null;

    #[Groups(["ms:read", "ms:write"])]
    public ?float $price = null;
}