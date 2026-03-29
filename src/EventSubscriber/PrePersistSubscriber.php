<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Entity\Shipment;
use App\Entity\Vehicle;
use App\Entity\Complaint;
use App\Entity\Rider;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::prePersist, priority: 500)]
class PrePersistSubscriber
{
    public function __construct(private Security $security)
    {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        // Set createdBy and timestamps for trackable entities
        if ($entity instanceof Shipment) {
            if ($entity->getCreatedBy() === null) {
                $entity->setCreatedBy($user);
            }
            if ($entity->getCreatedAt() === null) {
                $entity->setCreatedAt(new \DateTime());
            }
            if ($entity->getUpdatedAt() === null) {
                $entity->setUpdatedAt(new \DateTime());
            }
        } elseif ($entity instanceof Vehicle) {
            if ($entity->getCreatedBy() === null) {
                $entity->setCreatedBy($user);
            }
        } elseif ($entity instanceof Complaint) {
            if ($entity->getCreatedBy() === null) {
                $entity->setCreatedBy($user);
            }
            if ($entity->getCreatedAt() === null) {
                $entity->setCreatedAt(new \DateTime());
            }
        } elseif ($entity instanceof Rider) {
            if ($entity->getCreatedBy() === null) {
                $entity->setCreatedBy($user);
            }
            if ($entity->getCreatedAt() === null) {
                $entity->setCreatedAt(new \DateTime());
            }
        }
    }
}
