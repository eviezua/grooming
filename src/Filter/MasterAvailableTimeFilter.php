<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Bookings;
use App\Entity\Schedule;
use App\Enum\Weekdays;
use DateTimeImmutable;
use Doctrine\ORM\QueryBuilder;

final class MasterAvailableTimeFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void
    {
        if (!in_array($property, ['date_from', 'date_to', 'start_time', 'end_time'], true)) {
            return;
        }

        if (
            !isset($context['filters']['start_time'], $context['filters']['end_time'], $context['filters']['date_from'])
        ) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        $dateFrom = new DateTimeImmutable($context['filters']['date_from']);
        $dateTo = isset($context['filters']['date_to'])
            ? new DateTimeImmutable($context['filters']['date_to'])
            : $dateFrom;

        if ($dateTo < $dateFrom) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $requestedStart = $context['filters']['start_time'];
        $requestedEnd = $context['filters']['end_time'];

        if ($requestedStart >= $requestedEnd) {
            return;
        }

        $allDates = [];
        $current = $dateFrom;
        while ($current <= $dateTo) {
            $allDates[] = $current;
            $current = $current->modify('+1 day');
        }

        $weekdays = (count($allDates) >= 7)
            ? Weekdays::values()
            : array_values(array_unique(array_map(fn($d) => Weekdays::from($d->format('l'))->value, $allDates)));

        $em = $queryBuilder->getEntityManager();

        $scheduleData = $em->createQueryBuilder()
            ->select('IDENTITY(s.master) as master_id', 's.dayOfweek')
            ->from(Schedule::class, 's')
            ->where('s.dayOfweek IN (:weekdays)')
            ->andWhere('s.start_time <= :requestedStart')
            ->andWhere('s.stop_time >= :requestedEnd')
            ->setParameter('weekdays', $weekdays)
            ->setParameter('requestedStart', $requestedStart)
            ->setParameter('requestedEnd', $requestedEnd)
            ->getQuery()
            ->getArrayResult();

        $mastersByDay = [];
        foreach ($scheduleData as $row) {
            $dayValue = $row['dayOfweek'] instanceof Weekdays ? $row['dayOfweek']->value : $row['dayOfweek'];
            $mastersByDay[$dayValue][] = $row['master_id'];
        }

        if (!$mastersByDay) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        $candidateMasters = array_unique(array_merge(...array_values($mastersByDay)));

        $bookingData = $em->createQueryBuilder()
            ->select('IDENTITY(b.id_master) AS master_id', 'b.date', 'b.time_start', 'b.time_stop')
            ->from(Bookings::class, 'b')
            ->where('b.id_master IN (:masters)')
            ->andWhere('b.date BETWEEN :dateFrom AND :dateTo')
            ->setParameter('masters', $candidateMasters)
            ->setParameter('dateFrom', $dateFrom->format('Y-m-d'))
            ->setParameter('dateTo', $dateTo->format('Y-m-d'))
            ->getQuery()
            ->getArrayResult();

        $bookingsByMasterDate = [];
        foreach ($bookingData as $b) {
            $dateKey = $b['date'] instanceof \DateTimeInterface ? $b['date']->format('Y-m-d') : $b['date'];
            $bookingsByMasterDate[$b['master_id']][$dateKey][] = [
                'start' => $b['time_start'] instanceof \DateTimeInterface ? $b['time_start']->format(
                    'H:i'
                ) : $b['time_start'],
                'end' => $b['time_stop'] instanceof \DateTimeInterface ? $b['time_stop']->format(
                    'H:i'
                ) : $b['time_stop'],
            ];
        }

        $freeMasterIds = [];

        foreach ($candidateMasters as $masterId) {
            $availableDays = [];

            foreach ($allDates as $date) {
                $dayOfWeek = Weekdays::from($date->format('l'))->value;
                $dateKey = $date->format('Y-m-d');

                if (!isset($mastersByDay[$dayOfWeek]) || !in_array($masterId, $mastersByDay[$dayOfWeek], true)) {
                    continue;
                }

                $conflict = false;
                if (!empty($bookingsByMasterDate[$masterId][$dateKey])) {
                    $bookingTimes = $bookingsByMasterDate[$masterId][$dateKey];
                    $conflict = array_reduce(
                        $bookingTimes,
                        fn($carry, $b) => $carry || ($b['start'] < $requestedEnd && $b['end'] > $requestedStart),
                        false
                    );
                }

                if (!$conflict) {
                    $availableDays[] = $dateKey;
                }
            }

            if ($availableDays) {
                $freeMasterIds[] = $masterId;
            }
        }

        $freeMasterIds = array_values($freeMasterIds);

        if ($freeMasterIds) {
            $queryBuilder->andWhere("$alias.id IN (:freeMasterIds)")
                ->setParameter('freeMasterIds', $freeMasterIds);
        } else {
            $queryBuilder->andWhere('1 = 0');
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'date_from' => [
                'property' => 'date_from',
                'type' => 'string',
                'required' => false,
                'swagger' => ['description' => 'Start date (Y-m-d)'],
            ],
            'date_to' => [
                'property' => 'date_to',
                'type' => 'string',
                'required' => false,
                'swagger' => ['description' => 'End date (Y-m-d)'],
            ],
            'start_time' => [
                'property' => 'start_time',
                'type' => 'string',
                'required' => false,
                'swagger' => ['description' => 'Start time (H:i)'],
            ],
            'end_time' => [
                'property' => 'end_time',
                'type' => 'string',
                'required' => false,
                'swagger' => ['description' => 'End time (H:i)'],
            ],
        ];
    }
}