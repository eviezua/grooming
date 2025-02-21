<?php

namespace App\Factory;

use App\Entity\Bookings;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Bookings>
 */
final class BookingsFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Bookings::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     */
    protected function defaults(): array|callable
    {
        $startTime = self::faker()->dateTimeBetween('+1 day', '+1 day');
        $stopTime = self::faker()->dateTimeBetween('+1 day', '+2 days');

        if ($stopTime <= $startTime) {
            $stopTime = (clone $startTime)->modify('+1 hour');
        }
        return [
            'date' => self::faker()->dateTime(),
            'id_client' => ClientsFactory::createOne(),
            'id_master' => MastersFactory::createOne(),
            'pet' => PetsFactory::createOne(),
            'time_start' => $startTime,
            'time_stop' => $stopTime,
            'id_services' => ServicesFactory::CreateMany(rand(1, 3))
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Bookings $bookings): void {})
        ;
    }
}
