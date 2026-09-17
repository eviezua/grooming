<?php

namespace App\Factory;

use App\Entity\Masters;
use App\Enum\Status;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use function Zenstruck\Foundry\lazy;

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
            'id_city' => lazy(fn() => CitiesFactory::randomOrCreate()),
            'photo' => lazy(fn() => random_int(1, 6) . '.png'),
            'address' => self::faker()->address(),
            'name' => self::faker()->firstName(),
            'password' => self::faker()->password(),
            'surname' => self::faker()->lastName(),
            'avgRating' => self::faker()->randomFloat(2, 1, 5),
            'status' => self::faker()->randomElement(Status::cases()),
            'id_pets' => lazy(fn() => PetsFactory::repository()->count() >= 3
                ? PetsFactory::randomSet(rand(1, 3))
                : PetsFactory::createMany(rand(1, 3))
            )
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this->afterInstantiate(function(Masters $master, array $attributes): void {
            if (!$master->getDistrict()) {
                $city = $master->getIdCity();

                if ($city && !$city->getDistricts()->isEmpty()) {
                    $districts = $city->getDistricts()->toArray();
                    $master->setDistrict($districts[array_rand($districts)]);
                } else {
                    $districtProxy = DistrictsFactory::randomOrCreate();
                    $master->setDistrict($districtProxy->_real());
                }
            }

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
