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
        ServicesFactory::createMany(100);
        MastersFactory::createMany(100);
        ScheduleFactory::createMany(100);
        BookingsFactory::createMany(200);

        $manager->flush();
    }
}
