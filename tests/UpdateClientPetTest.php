<?php

namespace App\Tests;

use App\Enum\Species;
use App\Enum\Status;
use App\Factory\BookingsFactory;
use App\Factory\ClientsFactory;
use App\Factory\MastersFactory;
use App\Factory\PetsFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group client
 * @group booking
 * @group master
 * @group pet
 */
class UpdateClientPetTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    public function testUpdateClientPet(): void
    {
        $master = MastersFactory::createOne(['status' => Status::Approved]);
        $client = ClientsFactory::createOne();
        $pet = PetsFactory::createOne(['breed' => 'Random cat', 'spice' => Species::Cat, 'status' => Status::Approved]);

        $this->assertCount(0, $client->getPets());
        $this->assertFalse(
            $master->getIdPets()->contains($pet->_real()),
            'Master shouldn\'t contain new pet before booking updated.'
        );

        $booking = BookingsFactory::createOne(['id_client' => $client, 'id_master' => $master, 'pet' => $pet, 'status' => Status::Awaiting]);
        $booking->setStatus(Status::Approved);
        $booking->_save();

        $this->assertCount(1, $client->getPets());
        $this->assertEquals('Random cat', $client->getPets()->first()->getBreed());

        $this->assertTrue(
            $master->getIdPets()->contains($pet->_real()),
            'Master should contain new pet after booking update.'
        );
    }
}
