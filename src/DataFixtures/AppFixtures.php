<?php

namespace App\DataFixtures;

use App\Entity\Schedule;
use App\Entity\Services;
use App\Factory\BookingsFactory;
use App\Factory\CitiesFactory;
use App\Factory\ClientsFactory;
use App\Factory\MastersFactory;
use App\Factory\PetsFactory;
use App\Factory\ScheduleFactory;
use App\Factory\ServicesFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        PetsFactory::createMany(20);
        CitiesFactory::createMany(200);
        ServicesFactory::createMany(200);
        MastersFactory::createMany(200);
        ScheduleFactory::createMany(200);
        ClientsFactory::createMany(500);
        BookingsFactory::createMany(500);

        $manager->flush();
    }
}
