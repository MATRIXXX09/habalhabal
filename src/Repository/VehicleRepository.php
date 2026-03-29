<?php

namespace App\Repository;

use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vehicle>
 *
 * @method Vehicle|null find($id, $lockMode = null, $lockVersion = null)
 * @method Vehicle|null findOneBy(array $criteria, array $orderBy = null)
 * @method Vehicle[]    findAll()
 * @method Vehicle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

    /**
     * @return Vehicle[] Returns an array of available vehicles
     */
    public function findAvailableVehicles(): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.isAvailable = :val')
            ->setParameter('val', true)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Vehicle[] Returns an array of vehicles by type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.type = :type')
            ->setParameter('type', $type)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}