<?php

namespace App\Mapper;

use App\ApiResource\MastersServicesApi;
use App\Entity\Masters;
use App\Entity\MastersServices;
use App\Entity\Services;
use App\Service\EntityLoaderHelper;
use InvalidArgumentException;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: MastersServicesApi::class, to: MastersServices::class)]
class MastersServicesApiToEntityMapper implements MapperInterface
{
    public function __construct(private EntityLoaderHelper $loader)
    {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof MastersServicesApi);

        return $context['target_object'] ??
            ($from->id ? $this->loader->load(MastersServices::class, $from->id, 'MastersServices') : new MastersServices());    }

    public function populate(object $from, object $to, array $context): object
    {
        if (!$from instanceof MastersServicesApi) {
            throw new InvalidArgumentException('Expected MastersServicesApi object');
        }
        if (!$to instanceof MastersServices) {
            throw new InvalidArgumentException('Expected MastersServices object');
        }

        $to->setMaster($this->loader->load(Masters::class, $from->masterId, 'Masters'));
        $to->setService($this->loader->load(Services::class, $from->serviceId, 'Services'));
        $to->setPrice($from->price);

        return $to;
    }
}