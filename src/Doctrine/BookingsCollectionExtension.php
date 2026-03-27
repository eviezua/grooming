<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Bookings;
use App\Entity\Masters;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

class BookingsCollectionExtension implements QueryCollectionExtensionInterface
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
        $this->addWhere($queryBuilder, $resourceClass);
    }
    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        $user = $this->security->getUser();

        if (Bookings::class !== $resourceClass || $this->security->isGranted('ROLE_ADMIN') || !$user instanceof Masters) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder->addOrderBy(sprintf('%s.date', $rootAlias), 'DESC');
        $queryBuilder->addOrderBy(sprintf('%s.time_start', $rootAlias), 'DESC');

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $queryBuilder->andWhere(sprintf('%s.id_master = :current_user', $rootAlias))
            ->setParameter('current_user', $user->getId());
    }
}