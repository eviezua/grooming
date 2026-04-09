<?php

namespace App\Scheduler;

use App\Message\CheckUpcomingBookingsTrigger;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('default')]
final class CheckUpcomingBookingsTriggerSchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())->add(
            RecurringMessage::every('1 minute', new CheckUpcomingBookingsTrigger())
        );
    }
}
