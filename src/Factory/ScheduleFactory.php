<?php

namespace App\Factory;

use App\Entity\Schedule;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Schedule>
 */
final class ScheduleFactory extends PersistentProxyObjectFactory
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
        return Schedule::class;
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
            'dayOfweek' => self::faker()->text(255),
            'master' => MastersFactory::createOne(),
            'start_time' => $startTime,
            'stop_time' => $stopTime
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Schedule $schedule): void {})
        ;
    }
}
