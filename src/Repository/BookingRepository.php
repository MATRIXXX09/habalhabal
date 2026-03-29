<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 *
 * @method Booking|null find($id, $lockMode = null, $lockVersion = null)
 * @method Booking|null findOneBy(array $criteria, array $orderBy = null)
 * @method Booking[]    findAll()
 * @method Booking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function save(Booking $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Booking $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find available drivers for a specific time window
     */
    public function findAvailableDrivers(\DateTimeInterface $pickupTime, \DateTimeInterface $deliveryTime): array
    {
        return $this->getEntityManager()
            ->createQuery('
                SELECT r FROM App\Entity\Rider r
                WHERE r.status IN (\'available\')
                AND r.isAvailable = true
                AND r.id NOT IN (
                    SELECT b.assignedDriver FROM App\Entity\Booking b
                    WHERE b.status NOT IN (\'completed\', \'cancelled\')
                    AND (
                        (b.requestedPickupTime <= :deliveryTime AND b.requestedDeliveryTime >= :pickupTime)
                        OR b.requestedDeliveryTime IS NULL
                    )
                )
                ORDER BY r.createdAt DESC
            ')
            ->setParameter('pickupTime', $pickupTime)
            ->setParameter('deliveryTime', $deliveryTime)
            ->getResult();
    }

    /**
     * Find bookings for a specific driver
     */
    public function findByDriver($driver): array
    {
        return $this->findBy(['assignedDriver' => $driver], ['createdAt' => 'DESC']);
    }

    /**
     * Find active bookings (not completed or cancelled)
     */
    public function findActiveBookings(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.status NOT IN (:statuses)')
            ->setParameter('statuses', ['completed', 'cancelled'])
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
