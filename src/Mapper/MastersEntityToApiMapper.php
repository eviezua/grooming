<?php

namespace App\Mapper;

use App\ApiResource\MastersApi;
use App\Entity\Masters;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: Masters::class, to: MastersApi::class)]
class MastersEntityToApiMapper implements MapperInterface
{
    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof Masters);

        $to = new MastersApi();
        $to->id = $from->getId();
        $to->name = $from->getName();
        $to->surname = $from->getSurname();
        $to->avgRating = $from->getAvgRating();
        $to->servicesId = $from->getServicesId();
        $to->cityId = $from->getCityId();
        $to->districtId = $from->getDistrictId();
        $to->address = $from->getAddress();
        $to->petsId = $from->getPetsId();
        $to->email = $from->getEmail();
        $to->phone = $from->getPhone();
        $to->photo = $from->getPhoto();
        $to->schedulesId = $from->getSchedulesId();
        $to->bookingsId = $from->getBookingsId();
        $to->status = $from->getStatus()->value;

        return $to;
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof Masters);
        assert($to instanceof MastersApi);

        $to->id = $from->getId();
        $to->name = $from->getName();
        $to->surname = $from->getSurname();
        $to->avgRating = $from->getAvgRating();
        $to->servicesId = $from->getServicesId();
        $to->cityId = $from->getCityId();
        $to->districtId = $from->getDistrictId();
        $to->address = $from->getAddress();
        $to->petsId = $from->getPetsId();
        $to->email = $from->getEmail();
        $to->phone = $from->getPhone();
        $to->photo = $from->getPhoto();
        $to->schedulesId = $from->getSchedulesId();
        $to->bookingsId = $from->getBookingsId();
        $to->status = $from->getStatus()->value;

        return $to;
    }
}