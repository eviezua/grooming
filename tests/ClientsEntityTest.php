<?php

namespace App\Tests;

use App\Entity\Clients;
use App\Factory\PetsFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @group client
 * @group entity
 */
class ClientsEntityTest extends KernelTestCase
{
    use ResetDatabase, Factories;
    public function testClientsEntity(): void
    {
        $client = new Clients();
        $client->setName("Evie");
        $client->setSurname("Test");
        $client->setEmail("test@test.com");
        $client->setPhone("123456789");

        $pet = PetsFactory::createOne(['breed' => 'Norway forest cat']);
        $client->addPet($pet);

        $this->assertEquals('Evie', $client->getName());
        $this->assertEquals('Test', $client->getSurname());
        $this->assertEquals('test@test.com', $client->getEmail());
        $this->assertEquals('123456789', $client->getPhone());
        $this->assertEquals('Norway forest cat', $client->getPets()->first()->getBreed());
    }
}
