<?php

namespace App\Factory;

use App\Entity\Masters;
use App\Enum\Status;
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
            'address' => self::faker()->address(),
            'name' => self::faker()->firstName(),
            'password' => self::faker()->password(),
            'surname' => self::faker()->lastName(),
            'status' => self::faker()->randomElement(Status::cases()),
            'id_pets' => PetsFactory::CreateMany(rand(1, 3)),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this->afterInstantiate(function(Masters $master, array $attributes): void {
            $count = $attributes['services_count'] ?? 0;
            if ($count > 0) {
                foreach (ServicesFactory::createMany($count) as $service) {
                    MastersServicesFactory::createOne([
                        'master' => $master,
                        'service' => $service,
                        'price' => self::faker()->numberBetween(100, 1000),
                    ]);
                }
            }
        });
    }
}
