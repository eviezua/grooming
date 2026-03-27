<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\ApiResource\ClientsApi;
use App\Entity\Clients;
use App\Entity\Masters;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

class ClientsCollectionExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private Security $security) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void
    {
        $user = $this->security->getUser();

        if (
            !in_array($resourceClass, [ClientsApi::class, Clients::class]) ||
            !$user instanceof Masters ||
            $this->security->isGranted('ROLE_ADMIN')
        ) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->innerJoin(sprintf('%s.bookings', $rootAlias), 'b')
            ->andWhere('b.id_master = :current_master')
            ->setParameter('current_master', $user)
            ->addGroupBy(sprintf('%s.id', $rootAlias));
    }
}