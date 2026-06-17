<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\ApiResource\CitiesApi;
use App\ApiResource\DistrictsApi;
use App\ApiResource\MastersApi;
use App\ApiResource\PetsApi;
use App\ApiResource\ServicesApi;
use App\Entity\Cities;
use App\Entity\Districts;
use App\Entity\Masters;
use App\Entity\Pets;
use App\Entity\Services;
use App\Enum\Status;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

class StatusCollectionExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private readonly Security $security) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $allowedResources = [
            Cities::class,
            CitiesApi::class,
            Districts::class,
            DistrictsApi::class,
            Masters::class,
            MastersApi::class,
            Pets::class,
            PetsApi::class,
            Services::class,
            ServicesApi::class
        ];

        if (!in_array($resourceClass, $allowedResources, true)) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName('status');

        $queryBuilder
            ->andWhere(sprintf('%s.status = :%s', $rootAlias, $parameterName))
            ->setParameter($parameterName, Status::Approved->value);
    }
}