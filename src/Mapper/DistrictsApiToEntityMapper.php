<?php

namespace App\Mapper;

use App\ApiResource\DistrictsApi;
use App\Entity\Cities;
use App\Entity\Districts;
use App\Service\EntityLoaderHelper;
use InvalidArgumentException;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: DistrictsApi::class, to: Districts::class)]
class DistrictsApiToEntityMapper implements MapperInterface
{
    public function __construct(private EntityLoaderHelper $loader)
    {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof DistrictsApi);

        return new Districts();
    }

    public function populate(object $from, object $to, array $context): object
    {
        if (!$from instanceof DistrictsApi) {
            throw new InvalidArgumentException('Expected DistrictsApi object');
        }
        if (!$to instanceof Districts) {
            throw new InvalidArgumentException('Expected Districts object');
        }

        $to->setName($from->name);
        $to->setCity($this->loader->load(Cities::class, $from->cityId, 'Cities'));

        return $to;
    }
}