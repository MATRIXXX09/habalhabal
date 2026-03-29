<?php

namespace App\Repository;

use App\Entity\Rider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rider>
 *
 * @method Rider|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rider|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rider[]    findAll()
 * @method Rider[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RiderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rider::class);
    }

    public function findAvailableRiders(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isAvailable = :val')
            ->setParameter('val', true)
            ->orderBy('r.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}