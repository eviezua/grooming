<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

final class MasterIdFilter extends AbstractFilter
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
        if ($property !== 'id_masters' || empty($value)) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $msAlias = $queryNameGenerator->generateJoinAlias('mastersServices');
        $sAlias  = $queryNameGenerator->generateJoinAlias('master');

        $ids = is_array($value) ? $value : [$value];

        $queryBuilder->leftJoin("$rootAlias.mastersServices", $msAlias)
            ->leftJoin("$msAlias.master", $sAlias)
            ->andWhere($queryBuilder->expr()->in("$sAlias.id", $ids));
    }
    public function getDescription(string $resourceClass): array
    {
        return [
            'id_masters' => [
                'property' => 'id_masters',
                'type' => 'array',
                'required' => false,
                'isCollection' => true,
                'description' => 'Filter services by multiple masters IDs',
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
