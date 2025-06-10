<?php

namespace App\Mapper;

use App\ApiResource\ScheduleApi;
use App\Entity\Masters;
use App\Entity\Schedule;
use App\Enum\Weekdays;
use App\Service\EntityLoaderHelper;
use App\Service\TimeFormatter;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: ScheduleApi::class, to: Schedule::class)]
class ScheduleApiToEntityMapper implements MapperInterface
{
    public function __construct(
        private TimeFormatter $timeFormatter,
        private EntityLoaderHelper $loader
    ) {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof ScheduleApi);

        return $context['target_object'] ??
            ($from->id ? $this->loader->load(Schedule::class, $from->id, 'Schedule') : new Schedule());
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof ScheduleApi);
        assert($to instanceof Schedule);

        $to->setDayOfweek(Weekdays::from($from->dayOfweek));
        $to->setStartTime($this->timeFormatter->parseTime($from->start_time));
        $to->setStopTime($this->timeFormatter->parseTime($from->stop_time));
        $to->setMaster($this->loader->load(Masters::class, $from->masterId, 'Masters'));

        return $to;
    }
}