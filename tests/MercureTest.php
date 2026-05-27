<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * @group mercure
 */

class MercureTest extends KernelTestCase
{
    public function testMercureHubIsReachable(): void
    {
        self::bootKernel();

        $hub = self::getContainer()->get(HubInterface::class);

        $update = new Update(
            'https://example.com/mercure/hi',
            json_encode(['status' => 'test_success'])
        );

        try {
            $hub->publish($update);

            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Mercure hub is not reachable or JWT is invalid. Error: ' . $e->getMessage());
        }
    }
}
