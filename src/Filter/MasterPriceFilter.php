<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

final class MasterPriceFilter extends AbstractFilter
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
        if ($property !== 'budget') {
            return;
        }

        $filters = $context['filters'] ?? [];
        if (!isset($filters['services'], $filters['budget'])) {
            return;
        }

        $servicesRaw = $filters['services'];
        $budget = (float)$filters['budget'];

        $services = [];
        if (is_array($servicesRaw)) {
            array_walk_recursive($servicesRaw, function($v) use (&$services) {
                if ($v !== null && $v !== '') $services[] = $v;
            });
        } else {
            $services = array_filter(explode(',', (string)$servicesRaw));
        }
        $services = array_values(array_unique($services));
        $servicesCount = count($services);

        if ($servicesCount === 0) return;

        $alias = $queryBuilder->getRootAliases()[0];

        $subQueryBuilder = $queryBuilder->getEntityManager()->createQueryBuilder();
        $subAlias = 'ms_sub';

        $subQueryBuilder
            ->select("IDENTITY($subAlias.master)")
            ->from('App\Entity\MastersServices', $subAlias)
            ->where("$subAlias.service IN (:services)")
            ->groupBy("$subAlias.master")
            ->having("COUNT(DISTINCT $subAlias.service) = :servicesCount")
            ->andHaving("SUM($subAlias.price) <= :budget");

        $queryBuilder
            ->andWhere($queryBuilder->expr()->in("$alias.id", $subQueryBuilder->getDQL()))
            ->setParameter('services', $services)
            ->setParameter('servicesCount', $servicesCount)
            ->setParameter('budget', $budget);

        $order = $filters['order']['totalPrice'] ?? null;
        if ($order) {
            $subSortQuery = $queryBuilder->getEntityManager()->createQueryBuilder()
                ->select("SUM(ms_inner.price)")
                ->from('App\Entity\MastersServices', 'ms_inner')
                ->where("ms_inner.master = $alias")
                ->andWhere("ms_inner.service IN (:services)");

            $queryBuilder
                ->addSelect(sprintf('(%s) AS HIDDEN total_sum', $subSortQuery->getDQL()))
                ->addOrderBy('total_sum', strtoupper($order) === 'DESC' ? 'DESC' : 'ASC');
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'services' => [
                'property' => 'services',
                'type' => 'string',
                'required' => false,
                'swagger' => ['description' => 'Service IDs (array or comma-separated)'],
            ],
            'budget' => [
                'property' => 'budget',
                'type' => 'float',
                'required' => false,
                'swagger' => ['description' => 'Max budget'],
            ],
            'order[totalPrice]' => [
                'property' => 'totalPrice',
                'type' => 'string',
                'required' => false,
                'swagger' => ['description' => 'Sort (asc/desc)'],
            ],
        ];
    }
}