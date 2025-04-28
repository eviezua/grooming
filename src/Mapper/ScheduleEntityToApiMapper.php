<?php

namespace App\Mapper;

use App\ApiResource\ScheduleApi;
use App\Entity\Schedule;
use App\Service\TimeFormatter;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;

#[AsMapper(from: Schedule::class, to: ScheduleApi::class)]
class ScheduleEntityToApiMapper implements MapperInterface
{
    public function __construct(
        private TimeFormatter $timeFormatter
    ) {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        assert($from instanceof Schedule);

        $to = new ScheduleApi();
        $to->id = $from->getId();
        $to->dayOfweek = $from->getDayOfweek();
        $to->start_time = $this->timeFormatter->formatTime($from->getStartTime());
        $to->stop_time = $this->timeFormatter->formatTime($from->getStopTime());
        $to->masterId = $from->getMaster()->getId();

        return $to;
    }

    public function populate(object $from, object $to, array $context): object
    {
        assert($from instanceof Schedule);
        assert($to instanceof ScheduleApi);

        $to->id = $from->getId();
        $to->dayOfweek = $from->getDayOfweek();
        $to->start_time = $this->timeFormatter->formatTime($from->getStartTime());
        $to->stop_time = $this->timeFormatter->formatTime($from->getStopTime());
        $to->masterId = $from->getMasterId();

        return $to;
    }
}