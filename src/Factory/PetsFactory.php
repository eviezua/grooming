<?php

namespace App\Factory;

use App\Entity\Pets;
use App\Enum\Hair;
use App\Enum\Size;
use App\Enum\Species;
use App\Enum\Status;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Pets>
 */
final class PetsFactory extends PersistentProxyObjectFactory
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
        return Pets::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     */
    protected function defaults(): array|callable
    {
        return [
            'breed' => self::faker()->text(50),
            'cost_coficient' => self::faker()->randomFloat(nbMaxDecimals: 2, min: 1, max: 3),
            'hair' => self::faker()->randomElement(Hair::cases()),
            'size' => self::faker()->randomElement(Size::cases()),
            'spice' => self::faker()->randomElement(Species::cases()),
            'status' => self::faker()->randomElement(Status::cases()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Pets $pets): void {})
        ;
    }
}
