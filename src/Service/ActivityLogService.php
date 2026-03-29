<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ActivityLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {
    }

    /**
     * Log an activity
     */
    public function logActivity(
        string $action,
        ?string $targetData = null,
        ?User $user = null
    ): void {
        // Get current user from security context if not provided
        if ($user === null) {
            $user = $this->security->getUser();
        }

        // Only log if there's a user
        if (!$user instanceof User) {
            return;
        }

        $log = new ActivityLog();
        $log->setUserId($user->getId());
        $log->setUsername($user->getEmail());
        $log->setRole(implode(', ', $user->getRoles()));
        $log->setAction($action);
        $log->setTargetData($targetData);
        $log->setDateTime(new \DateTime());

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    /**
     * Log login activity
     */
    public function logLogin(User $user): void
    {
        $this->logActivity('LOGIN', null, $user);
    }

    /**
     * Log logout activity
     */
    public function logLogout(User $user): void
    {
        $this->logActivity('LOGOUT', null, $user);
    }

    /**
     * Log creation of an entity
     */
    public function logCreate(string $entityType, ?string $details = null): void
    {
        $targetData = "Created " . $entityType;
        if ($details) {
            $targetData .= ": " . $details;
        }
        $this->logActivity('CREATE', $targetData);
    }

    /**
     * Log update of an entity
     */
    public function logUpdate(string $entityType, ?string $details = null): void
    {
        $targetData = "Updated " . $entityType;
        if ($details) {
            $targetData .= ": " . $details;
        }
        $this->logActivity('UPDATE', $targetData);
    }

    /**
     * Log deletion of an entity
     */
    public function logDelete(string $entityType, ?string $details = null): void
    {
        $targetData = "Deleted " . $entityType;
        if ($details) {
            $targetData .= ": " . $details;
        }
        $this->logActivity('DELETE', $targetData);
    }

    /**
     * Log custom action
     */
    public function logCustom(string $action, ?string $targetData = null): void
    {
        $this->logActivity($action, $targetData);
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities(int $limit = 10): array
    {
        return $this->entityManager->getRepository(ActivityLog::class)
            ->createQueryBuilder('log')
            ->orderBy('log.dateTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get activities by user
     */
    public function getActivitiesByUser(User $user, int $limit = 10): array
    {
        return $this->entityManager->getRepository(ActivityLog::class)
            ->createQueryBuilder('log')
            ->where('log.userId = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('log.dateTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get activities by action
     */
    public function getActivitiesByAction(string $action, int $limit = 10): array
    {
        return $this->entityManager->getRepository(ActivityLog::class)
            ->createQueryBuilder('log')
            ->where('log.action = :action')
            ->setParameter('action', $action)
            ->orderBy('log.dateTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get activities in date range
     */
    public function getActivitiesByDateRange(\DateTime $from, \DateTime $to): array
    {
        return $this->entityManager->getRepository(ActivityLog::class)
            ->createQueryBuilder('log')
            ->where('log.dateTime >= :from')
            ->andWhere('log.dateTime <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('log.dateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
