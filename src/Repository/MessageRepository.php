<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findUnreadMessagesForAdmin(User $admin): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.admin = :admin')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('admin', $admin)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countUnreadMessagesForAdmin(User $admin): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.admin = :admin')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('admin', $admin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllMessagesForAdmin(User $admin): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.admin = :admin')
            ->setParameter('admin', $admin)
            ->orderBy('m.readAt', 'ASC')
            ->addOrderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
