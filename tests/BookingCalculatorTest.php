<?php

namespace App\Tests;

use App\Entity\Bookings;
use App\Enum\Hair;
use App\Enum\Size;
use App\Enum\Species;
use App\Factory\ClientsFactory;
use App\Factory\MastersFactory;
use App\Factory\MastersServicesFactory;
use App\Factory\PetsFactory;
use App\Factory\ServicesFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group booking
 * @group entity
 */
class BookingCalculatorTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testBookingPriceCalculatesWithPetCoefficient(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get('doctrine')->getManager();

        $service = ServicesFactory::createOne(['cost' => 100.0])->_real();

        $master = MastersFactory::createOne()->_real();
        $masterService = MastersServicesFactory::createOne([
            'master' => $master,
            'service' => $service,
            'price' => 150.0,
        ])->_real();

        $pet = PetsFactory::createOne([
            'spice' => Species::Dog,
            'size' => Size::Small,
            'hair' => Hair::Short,
        ])->_real();

        $em->flush();

        $this->assertEquals(1.54, $pet->getCostCoficient());

        $client = ClientsFactory::createOne()->_real();

        $booking = new Bookings();
        $booking->setIdMaster($master);
        $booking->addIdService($service);
        $booking->setPet($pet);
        $booking->setIdClient($client);
        $booking->setDate(new \DateTime('next Monday'));
        $booking->setTimeStart(new \DateTime('10:00'));
        $booking->setTimeStop(new \DateTime('11:00'));

        $em->persist($booking);
        $em->flush();

        $expectedPrice = 150.0 * 1.54;

        $this->assertEquals($expectedPrice, $booking->getTotalPrice());

        $masterService->setPrice(500.0);
        $em->persist($masterService);
        $em->flush();

        $em->refresh($booking);

        $this->assertEquals($expectedPrice, $booking->getTotalPrice());
    }
}
