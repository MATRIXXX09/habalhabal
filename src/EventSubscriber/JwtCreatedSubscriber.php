<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JwtCreatedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [Events::JWT_CREATED => 'onJwtCreated'];
    }

    public function onJwtCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $data = $event->getData();
        $data['username'] = $user->getUserIdentifier();
        $data['display_name'] = $user->getUsername() ?? $user->getUserIdentifier();
        $event->setData($data);
    }
}