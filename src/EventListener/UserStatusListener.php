<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class UserStatusListener implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private RouterInterface $router
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Skip check on login/logout/register pages to prevent redirect loops
        $route = $event->getRequest()->attributes->get('_route');
        if (in_array($route, ['app_login', 'app_logout', 'app_register', 'app_home'])) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        
        if (!$token) {
            return;
        }

        $user = $token->getUser();

        // Check if user is disabled or archived
        if ($user instanceof User && !$user->isEnabled()) {
            // Clear the security token first
            $this->tokenStorage->setToken(null);
            
            // Get the session and add flash before invalidating
            $session = $event->getRequest()->getSession();
            if ($session->isStarted()) {
                $session->getFlashBag()->add('error', 'Your account has been disabled or archived. Please contact an administrator.');
            }
            
            // Invalidate the session
            $session->invalidate();
            
            // Redirect to login
            $response = new RedirectResponse($this->router->generate('app_login'));
            $event->setResponse($response);
        }
    }
}
