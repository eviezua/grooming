<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use DateTime;
use Doctrine\ORM\QueryBuilder;

final class MasterAvailableTimeFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (!in_array($property, ['start_time', 'stop_time'])) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if (!in_array('schedule', $queryBuilder->getAllAliases())) {
            $queryBuilder->leftJoin("$alias.schedules", 'schedule');
        }

        if ($property === 'start_time') {
            $queryBuilder->andWhere('schedule.start_time >= :startTime')
                ->setParameter('startTime', new DateTime($value));
        }

        if ($property === 'stop_time') {
            $queryBuilder->andWhere('schedule.stop_time <= :stopTime')
                ->setParameter('stopTime', new DateTime($value));
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'start_time' => [
                'property' => 'start_time',
                'type' => 'string',
                'required' => false,
                'swagger' => ['description' => 'Filter masters whose schedules start after or at this time (HH:MM:SS)'],
            ],
            'stop_time' => [
                'property' => 'stop_time',
                'type' => 'string',
                'required' => false,
                'swagger' => ['description' => 'Filter masters whose schedules end before or at this time (HH:MM:SS)'],
            ],
        ];
    }
}