<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class AuthenticationActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onLogin',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof User) {
            $log = new ActivityLog();
            $log->setUserId($user->getId());
            $log->setUsername($user->getEmail());
            $log->setRole(implode(', ', $user->getRoles()));
            $log->setAction('LOGIN');
            $log->setTargetData(sprintf('User: %s (ID: %s)', $user->getEmail(), $user->getId()));
            $log->setDateTime(new \DateTime());

            $this->entityManager->persist($log);
            $this->entityManager->flush();
        }
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();

        if ($user instanceof User) {
            $log = new ActivityLog();
            $log->setUserId($user->getId());
            $log->setUsername($user->getEmail());
            $log->setRole(implode(', ', $user->getRoles()));
            $log->setAction('LOGOUT');
            $log->setTargetData(sprintf('User: %s (ID: %s)', $user->getEmail(), $user->getId()));
            $log->setDateTime(new \DateTime());

            $this->entityManager->persist($log);
            $this->entityManager->flush();
        }
    }
}
