<?php

namespace App\Repository;

use App\Entity\Performance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Performance|null find($id, $lockMode = null, $lockVersion = null)
 * @method Performance|null findOneBy(array $criteria, array $orderBy = null)
 * @method Performance[]    findAll()
 * @method Performance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PerformanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Performance::class);
    }

    /**
     * Classement des buteurs
     * @return Performance[] Returns an array of Performance objects
     */
    public function rankingGoals() :?array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.player', 'pl')
            ->addSelect('pl')
            ->select('pl.id,
                pl.name,
                pl.current_team,
                SUM(p.goal) AS total')
            ->groupBy('p.player')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Classement des passeurs
     * @return Performance[] Returns an array of Performance objects
     */
    public function rankingAssists() :?array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.player', 'pl')
            ->addSelect('pl')
            ->select('pl.id,
                pl.name,
                pl.current_team,
                SUM(p.assist) AS total')
            ->groupBy('p.player')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
    

    /*
    public function findOneBySomeField($value): ?Performance
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
