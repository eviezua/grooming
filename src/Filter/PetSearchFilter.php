<?php

namespace App\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Elastic\Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class PetSearchFilter extends AbstractElasticSearchFilter
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger,
        Client $client,
        ?NameConverterInterface $nameConverter = null
    ) {
        parent::__construct(
            $managerRegistry,
            $logger,
            $client,
            'pets',
            ['breed'],
            $nameConverter
        );
    }
}
