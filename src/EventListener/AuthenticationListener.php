<?php

namespace App\EventListener;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class AuthenticationListener implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface $router,
        private EntityManagerInterface $entityManager
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof User) {
            // Log the login
            $this->logActivity($user, 'USER_LOGIN');
            
            // Check if user is disabled
            if (!$user->isEnabled()) {
                // Invalidate the session
                $event->getRequest()->getSession()->invalidate();
                
                // Add flash message
                $event->getRequest()->getSession()->getFlashBag()->add('error', 'Your account has been disabled or archived. Please contact an administrator.');
                
                // Redirect to login
                $response = new RedirectResponse($this->router->generate('app_login'));
                $event->getRequest()->attributes->set('_response', $response);
            }
        }
    }

    private function logActivity(User $user, string $action): void
    {
        try {
            $log = new ActivityLog();
            $log->setUserId($user->getId());
            $log->setUsername($user->getUserIdentifier());
            $log->setRole(implode(', ', $user->getRoles()));
            $log->setAction($action);
            
            // Format: "User: username (ID: {id})"
            $targetData = sprintf('User: %s (ID: %d)', $user->getUserIdentifier(), $user->getId());
            $log->setTargetData($targetData);
            $log->setDateTime(new \DateTime());
            
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Silently fail logging
            error_log('Failed to log authentication event: ' . $e->getMessage());
        }
    }
}
