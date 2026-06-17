<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Elastic\Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

abstract class AbstractElasticSearchFilter extends AbstractFilter
{
    protected Client $client;
    protected string $indexName;
    protected array $fields;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger,
        Client $client,
        string $indexName,
        array $fields,
        ?NameConverterInterface $nameConverter = null
    ) {
        parent::__construct($managerRegistry, $logger, $nameConverter);
        $this->client = $client;
        $this->indexName = $indexName;
        $this->fields = $fields;
    }

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if ('search' !== $property || !$value) {
            return;
        }

        $trimmedValue = trim($value);

        $body = [
            'query' => [
                'bool' => [
                    'should' => [
                        [
                            'multi_match' => [
                                'query' => $trimmedValue,
                                'fields' => $this->fields,
                                'type' => 'cross_fields',
                                'operator' => 'and',
                                'boost' => 20.0
                            ],
                        ],
                        [
                            'multi_match' => [
                                'query' => $trimmedValue,
                                'fields' => $this->fields,
                                'operator' => 'or',
                                'fuzziness' => 'AUTO',
                                'boost' => 1.0
                            ],
                        ],
                    ],
                    'minimum_should_match' => 1,
                ],
            ],
        ];

        $result = $this->client->search([
            'index' => $this->indexName,
            'size' => 200,
            'body' => $body,
        ]);

        $debugHits = array_map(fn($hit) => [
            'id' => $hit['_id'],
            'score' => $hit['_score'],
            'name' => $hit['_source']['name'] ?? 'unknown',
            'surname' => $hit['_source']['surname'] ?? 'unknown',
        ], $result['hits']['hits']);

        if ($this->logger) {
            $this->logger->info('ELASTICSEARCH RAW HITS:', $debugHits);
        }

        $ids = array_map(fn($hit) => $hit['_id'], $result['hits']['hits']);

        if (count($ids) > 0) {
            $alias = $queryBuilder->getRootAliases()[0];

            $queryBuilder
                ->andWhere("$alias.id IN (:elastic_ids)")
                ->setParameter('elastic_ids', $ids);

            $orderByCase = 'CASE ';
            foreach ($ids as $index => $id) {
                $orderByCase .= "WHEN $alias.id = :id_$index THEN $index ";
                $queryBuilder->setParameter("id_$index", $id);
            }
            $orderByCase .= 'ELSE 9999 END';

            $queryBuilder->orderBy($orderByCase, 'ASC');

        } else {
            $queryBuilder->andWhere('1 = 0');
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => 'string',
                'required' => false,
            ],
        ];
    }
}