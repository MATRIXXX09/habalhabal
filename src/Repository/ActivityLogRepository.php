<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    /**
     * Find logs with optional filtering
     */
    public function findWithFilters(?string $userEmail = null, ?string $action = null, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null)
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.dateTime', 'DESC');

        if ($userEmail) {
            $qb->andWhere('a.username LIKE :userEmail')
                ->setParameter('userEmail', '%' . $userEmail . '%');
        }

        if ($action) {
            $qb->andWhere('a.action = :action')
                ->setParameter('action', $action);
        }

        if ($dateFrom) {
            $qb->andWhere('a.dateTime >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb->andWhere('a.dateTime <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        return $qb->getQuery()->getResult();
    }
}

