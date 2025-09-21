<?php

namespace App\DataFixtures;

use App\Entity\Masters;
use App\Entity\Schedule;
use App\Entity\Services;
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
        CitiesFactory::createMany(200);
        $cities = CitiesFactory::repository()->findAll();

        foreach ($cities as $city) {
            DistrictsFactory::createMany(5, ['city' => $city]);
        }
        ServicesFactory::createMany(200);
        MastersFactory::createMany(200);

        $masters = $manager->getRepository(Masters::class)->findAll();

        foreach ($masters as $master) {
            $city = $master->getIdCity();
            $rating = $master->getAvgRating();
            if ($city) {
                $districts = $city->getDistricts()->toArray();
                if (!empty($districts)) {
                    $randomDistrict = $districts[array_rand($districts)];
                    $master->setDistrict($randomDistrict);
                }
            }
            if($rating == 0){
                $master->setAvgRating(rand(1,5));
            }
            if (!$master->getPhoto()) {
                $photoNumber = random_int(1, 6);
                $master->setPhoto("{$photoNumber}.png");
            }
            $manager->persist($master);
        }

        ScheduleFactory::createMany(200);
        ClientsFactory::createMany(500);
        BookingsFactory::createMany(500);

        $manager->flush();
    }
}
