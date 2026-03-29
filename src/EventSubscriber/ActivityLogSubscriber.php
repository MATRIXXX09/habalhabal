<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::postPersist, priority: 500)]
#[AsDoctrineListener(event: Events::postUpdate, priority: 500)]
#[AsDoctrineListener(event: Events::postRemove, priority: 500)]
class ActivityLogSubscriber
{
    public function __construct(private Security $security)
    {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->logActivity($args, 'CREATE');
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->logActivity($args, 'UPDATE');
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->logActivity($args, 'DELETE');
    }

    private function logActivity(LifecycleEventArgs $args, string $action): void
    {
        try {
            $entity = $args->getObject();
            $user = $this->security->getUser();

            // Only log specific entities to avoid clutter
            $loggableEntities = ['User', 'Shipment', 'Vehicle', 'Complaint', 'Rider', 'Booking'];
            $entityClass = (new \ReflectionClass($entity))->getShortName();

            // Don't log ActivityLog itself to avoid infinite loop
            if ($entityClass === 'ActivityLog' || !in_array($entityClass, $loggableEntities)) {
                return;
            }

            // Only log if there's an authenticated user
            if (!$user instanceof User) {
                return;
            }

            $log = new ActivityLog();
            $log->setUserId($user->getId());
            $log->setUsername($user->getEmail());
            $log->setRole(implode(', ', $user->getRoles()));
            $log->setAction($action);
            
            // Create target data string
            $targetData = sprintf(
                '%s: %s (ID: %s)',
                $entityClass,
                $this->getEntityIdentifier($entity),
                $this->getEntityId($entity)
            );
            $log->setTargetData($targetData);
            $log->setDateTime(new \DateTime());

            // Persist the log without triggering another postPersist event
            $manager = $args->getObjectManager();
            $manager->persist($log);
            $manager->flush();
        } catch (\Exception $e) {
            // Log errors but don't fail the main operation
            error_log('Error in ActivityLogSubscriber: ' . $e->getMessage());
        }
    }

    private function getEntityId($entity): ?int
    {
        $reflection = new \ReflectionClass($entity);
        $idProperty = $reflection->hasProperty('id') ? $reflection->getProperty('id') : null;
        
        if ($idProperty) {
            $idProperty->setAccessible(true);
            return $idProperty->getValue($entity);
        }

        return null;
    }

    private function getEntityIdentifier($entity): string
    {
        // Return meaningful identifier based on entity type
        if ($entity instanceof User) {
            return $entity->getEmail();
        }

        // Add specific handling for other entities
        if (method_exists($entity, 'getPlateNumber')) {
            // Vehicle entity
            try {
                $plateNumber = $entity->getPlateNumber();
                if ($plateNumber) {
                    return 'Plate: ' . $plateNumber;
                }
            } catch (\Exception $e) {
                // Handle lazy loading errors
            }
        }

        if (method_exists($entity, 'getFirstName') && method_exists($entity, 'getLastName')) {
            // Rider/User-like entity
            try {
                $firstName = $entity->getFirstName();
                $lastName = $entity->getLastName();
                if ($firstName || $lastName) {
                    return trim($firstName . ' ' . $lastName);
                }
            } catch (\Exception $e) {
                // Handle lazy loading errors, return fallback
            }
        }

        // Try common identifier properties
        $identifierProperties = ['name', 'title', 'email', 'bookingType', 'plateNumber'];
        $reflection = new \ReflectionClass($entity);

        foreach ($identifierProperties as $prop) {
            try {
                if ($reflection->hasProperty($prop)) {
                    $property = $reflection->getProperty($prop);
                    $property->setAccessible(true);
                    $value = $property->getValue($entity);
                    if ($value) {
                        return (string) $value;
                    }
                }
            } catch (\Exception $e) {
                // Handle lazy loading errors, continue to next property
                continue;
            }
        }

        return 'Unknown';
    }
}
