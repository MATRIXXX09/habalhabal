<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AccountStatusSubscriber implements EventSubscriberInterface
{
    use TargetPathTrait;

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private UserRepository $userRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

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

        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();

        // Only check if user is authenticated
        if (!$token || !$token->getUser() instanceof User) {
            return;
        }

        $user = $token->getUser();

        // Skip login, logout, and clear session routes to avoid interference
        $route = $request->attributes->get('_route');
        if (in_array($route, ['app_login', 'app_logout', 'app_register', 'app_clear_disabled_session'])) {
            return;
        }

        // Check if user is still enabled in database
        $dbUser = $this->userRepository->find($user->getId());
        
        if (!$dbUser || !$dbUser->isEnabled()) {
            // User is disabled - store info and logout
            $session = $request->getSession();
            $session->set('account_disabled', true);
            $session->set('disabled_user_email', $user->getEmail());
            $session->set('disabled_user_status', $dbUser ? $dbUser->getStatus() : 'unknown');

            // Clear authentication token
            $this->tokenStorage->setToken(null);

            // Invalidate session
            $session->invalidate();

            // Redirect to login
            $response = new RedirectResponse($this->urlGenerator->generate('app_login'));
            $event->setResponse($response);
        }
    }
}
