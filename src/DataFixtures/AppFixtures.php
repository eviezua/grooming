<?php

namespace App\DataFixtures;

use App\Entity\Masters;
use App\Enum\Weekdays;
use App\Factory\BookingsFactory;
use App\Factory\CitiesFactory;
use App\Factory\ClientsFactory;
use App\Factory\DistrictsFactory;
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
        $cities = CitiesFactory::createMany(50);
        foreach ($cities as $city) {
            DistrictsFactory::createMany(5, ['city' => $city]);
        }
        ServicesFactory::createMany(100);

        $masters = MastersFactory::createMany(100);
        $weekdaysEnum = Weekdays::cases();

        foreach ($masters as $master) {
            $workingDaysKeys = (array) array_rand($weekdaysEnum, 5);
            foreach ($workingDaysKeys as $key) {
                ScheduleFactory::createOne([
                    'master' => $master,
                    'dayOfweek' => $weekdaysEnum[$key],
                ]);
            }
        }

        ClientsFactory::createMany(200);
        BookingsFactory::createMany(300);

        $manager->flush();
    }
}
