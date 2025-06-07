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
        $startHour = self::faker()->numberBetween(0, 23);
        $startTime = (new \DateTime())->setTime($startHour, self::faker()->numberBetween(0, 59));
        $stopHour = self::faker()->numberBetween($startHour + 1, 24);
        $stopTime = (clone $startTime)->setTime($stopHour % 24, self::faker()->numberBetween(0, 59));

        return [
            'date' => self::faker()->dateTime(),
            'id_client' => ClientsFactory::createOne(),
            'id_master' => MastersFactory::createOne(),
            'pet' => PetsFactory::createOne(),
            'time_start' => $startTime,
            'time_stop' => $stopTime,
            'id_services' => ServicesFactory::CreateMany(rand(1, 3)),
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
