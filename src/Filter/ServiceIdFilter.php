<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;

final class ServiceIdFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void
    {
        if ($property !== 'id_services' || empty($value)) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $msAlias = $queryNameGenerator->generateJoinAlias('mastersServices');
        $sAlias  = $queryNameGenerator->generateJoinAlias('service');

        $ids = is_array($value) ? $value : [$value];

        $queryBuilder->leftJoin("$rootAlias.mastersServices", $msAlias)
            ->leftJoin("$msAlias.service", $sAlias)
            ->andWhere($queryBuilder->expr()->in("$sAlias.id", $ids));
    }
    public function getDescription(string $resourceClass): array
    {
        return [
            'id_services' => [
                'property' => 'id_services',
                'type' => 'array',
                'required' => false,
                'isCollection' => true,
                'description' => 'Filter masters by multiple service IDs',
                'openapi' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'style' => 'form',
                    'explode' => true,
                ],
            ],
        ];
    }
}
