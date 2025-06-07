<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use Exception;

final class TimeBetweenFilter extends AbstractFilter
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
        if (!in_array($property, ['time_from', 'time_to']) || !is_array($context['filters'] ?? null)) {
            return;
        }

        $filters = $context['filters'];

        if (empty($filters['time_from']) || empty($filters['time_to'])) {
            return;
        }

        try {
            $from = new DateTime($filters['time_from']);
            $to = new DateTime($filters['time_to']);
        } catch (Exception $e) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->andWhere("$alias.time_start BETWEEN :from AND :to")
            ->andWhere("$alias.time_stop BETWEEN :from AND :to")
            ->setParameter('from', $from)
            ->setParameter('to', $to);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'time_from' => [
                'property' => 'time_from',
                'type' => 'string',
                'required' => false,
                'swagger' => [
                    'description' => 'Start of the filter interval (inclusive)',
                    'name' => 'From time',
                    'type' => 'string',
                ],
            ],
            'time_to' => [
                'property' => 'time_to',
                'type' => 'string',
                'required' => false,
                'swagger' => [
                    'description' => 'End of the filter interval (inclusive)',
                    'name' => 'To time',
                    'type' => 'string',
                ],
            ],
        ];
    }
}