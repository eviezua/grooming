<?php

namespace App\Repository;

use App\Entity\Bookings;
use App\Enum\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bookings>
 */
class BookingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bookings::class);
    }

    public function findForNotification(string $date, string $time, string $field = 'time_start'): array
    {
        $start = $time . ':00';
        $end = $time . ':59';

        if (!in_array($field, ['time_start', 'time_stop'])) {
            throw new \InvalidArgumentException("Invalid field name");
        }

        return $this->createQueryBuilder('b')
            ->where('b.date = :date')
            ->andWhere("b.$field BETWEEN :start AND :end")
            ->andWhere('b.status = :status')
            ->setParameter('date', $date)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', Status::Approved->value)
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Bookings[] Returns an array of Bookings objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Bookings
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
