<?php

namespace App\Factory;

use App\Entity\Schedule;
use App\Enum\Weekdays;
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
        $startHour = self::faker()->numberBetween(0, 23);
        $startTime = (new \DateTime())->setTime($startHour, self::faker()->numberBetween(0, 59));
        $stopHour = self::faker()->numberBetween($startHour + 1, 24);
        $stopTime = (clone $startTime)->setTime($stopHour % 24, self::faker()->numberBetween(0, 59));

        if ($stopTime <= $startTime) {
            $stopTime = (clone $startTime)->modify('+1 hour');
        }
        $startTime = new \DateTime($startTime->format('Y-m-d H:i:s'));
        $stopTime = new \DateTime($stopTime->format('Y-m-d H:i:s'));
        return [
            'dayOfweek' => self::faker()->randomElement(Weekdays::cases()),
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
