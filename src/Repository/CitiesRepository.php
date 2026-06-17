<?php

namespace App\Repository;

use App\Entity\Cities;
use App\Enum\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cities>
 */
class CitiesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cities::class);
    }

    public function findFirstFallbackCity(int $excludedCityId): ?Cities
    {
        $city = $this->createQueryBuilder('c')
            ->where('c.id != :excludedId')
            ->andWhere('c.status = :status')
            ->setParameter('excludedId', $excludedCityId)
            ->setParameter('status', Status::Approved)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$city) {
            $city = $this->createQueryBuilder('c')
                ->where('c.id != :excludedId')
                ->setParameter('excludedId', $excludedCityId)
                ->orderBy('c.id', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return $city;
    }

//    /**
//     * @return Cities[] Returns an array of Cities objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Cities
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
