<?php

namespace App\Mapper;

use App\ApiResource\ServicesApi;
use App\Entity\Services;
use App\Service\EntityLoaderHelper;
use DateTimeImmutable;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: ServicesApi::class, to: Services::class)]
class ServicesApiToEntityMapper implements MapperInterface
{
    public function __construct(
        private EntityLoaderHelper $loader
    ) {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof ServicesApi);

        return $context['target_object'] ??
            ($from->id ? $this->loader->load(Services::class, $from->id, 'Services') : new Services());
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof ServicesApi);
        assert($to instanceof Services);

        $to->setName($from->name);
        $to->setCost($from->cost);
        if ($from->default_time) {
            $to->setDefaultTime(new DateTimeImmutable($from->default_time));
        }

        return $to;
    }
}