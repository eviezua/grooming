<?php

namespace App\Factory;

use App\Entity\Bookings;
use App\Enum\Status;
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
            'id_client' => ClientsFactory::new(),
            'id_master' => MastersFactory::new(),
            'pet' => PetsFactory::new(),
            'time_start' => $startTime,
            'time_stop' => $stopTime,
            'status' => self::faker()->randomElement(Status::cases())
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
