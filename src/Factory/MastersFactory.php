<?php

namespace App\Factory;

use App\Entity\Masters;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Masters>
 */
final class MastersFactory extends PersistentProxyObjectFactory
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
        return Masters::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     */
    protected function defaults(): array|callable
    {
        return [
            'email' => self::faker()->email(),
            'id_city' => CitiesFactory::createOne(),
            'name' => self::faker()->firstName(),
            'password' => self::faker()->password(),
            'surname' => self::faker()->lastName(),
            'id_services' => ServicesFactory::CreateMany(rand(1, 3)),
            'id_pets' => PetsFactory::CreateMany(rand(1, 3)),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Masters $masters): void {})
        ;
    }
}
