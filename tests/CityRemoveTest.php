<?php

namespace App\Tests;

use App\Entity\Cities;
use App\Enum\Status;
use App\Factory\CitiesFactory;
use App\Factory\MastersFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group city
 * @group entity
 */
class CityRemoveTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testCityRemovalReassignsMastersCorrectly(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get('doctrine')->getManager();

        $cityToDelete = CitiesFactory::createOne(['status' => Status::Approved])->_real();
        $fallbackApprovedCity = CitiesFactory::createOne(['status' => Status::Approved])->_real();
        $fallbackAwaitingCity = CitiesFactory::createOne(['status' => Status::Awaiting])->_real();

        $master = MastersFactory::createOne(['id_city' => $cityToDelete])->_real();

        $em->remove($cityToDelete);
        $em->flush();

        $em->refresh($master);
        $this->assertSame($fallbackApprovedCity->getId(), $master->getIdCity()->getId());
        $this->assertNull($master->getDistrict(), 'District should be nullified after city change');

        $em->remove($fallbackApprovedCity);
        $em->flush();

        $em->refresh($master);
        $this->assertSame($fallbackAwaitingCity->getId(), $master->getIdCity()->getId());

        $allCities = $em->getRepository(Cities::class)->findAll();
        foreach ($allCities as $city) {
            if ($city->getId() !== $fallbackAwaitingCity->getId()) {
                $em->remove($city);
            }
        }
        $em->flush();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete the city. No alternative city found to reassign the active masters.');

        $em->remove($fallbackAwaitingCity);
        $em->flush();
    }
}
