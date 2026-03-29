<?php

namespace App\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Events;
use Doctrine\ORM\Events as ORMEvents;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsDoctrineListener(event: ORMEvents::postLoad)]
class TimezoneListener
{
    private const TIMEZONE = 'Asia/Manila';

    public function postLoad(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        
        // Handle DateTime fields
        $reflection = new \ReflectionClass($entity);
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($entity);
            
            if ($value instanceof \DateTimeImmutable || $value instanceof \DateTime) {
                // Convert UTC to Manila time
                $timezone = new \DateTimeZone(self::TIMEZONE);
                if ($value instanceof \DateTimeImmutable) {
                    $convertedValue = $value->setTimezone($timezone);
                } else {
                    $convertedValue = $value->setTimezone($timezone);
                }
                $property->setValue($entity, $convertedValue);
            }
        }
    }
}
