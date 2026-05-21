<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Exception\InvalidStateAuthenticationException;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    private ClientRegistry $clientRegistry;
    private UrlGeneratorInterface $urlGenerator;
    private EntityManagerInterface $entityManager;
    private ?LoggerInterface $logger;

    public function __construct(
        ClientRegistry $clientRegistry,
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager,
        ?LoggerInterface $logger = null
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    private function log(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->debug($message, $context);
        }
    }

    public function supports(Request $request): ?bool
    {
        $route = $request->attributes->get('_route');
        $path = $request->getPathInfo();
        
        $this->log("GoogleAuthenticator.supports() called", ['route' => $route, 'path' => $path]);
        
        $isOAuthCallback = ($route === 'connect_google_check' || strpos($path, '/connect/google/check') === 0);
        
        $this->log("GoogleAuthenticator.supports() result", ['supports' => $isOAuthCallback]);
        
        return $isOAuthCallback;
    }

    public function authenticate(Request $request): Passport
    {
        try {
            $this->log("GoogleAuthenticator.authenticate() starting");
            
            $client = $this->clientRegistry->getClient('google');
            $redirectUri = $this->urlGenerator->generate('connect_google_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
            if (str_starts_with($redirectUri, 'https://127.0.0.1') || str_starts_with($redirectUri, 'https://localhost')) {
                $redirectUri = preg_replace('/^https:/', 'http:', $redirectUri);
            }

            $this->log('Using redirect_uri for token exchange', ['redirect_uri' => $redirectUri]);

            try {
                $accessToken = $this->fetchAccessToken($client, ['redirect_uri' => $redirectUri]);
            } catch (InvalidStateAuthenticationException $stateException) {
                $host = $request->getHost();
                $isLocalHost = in_array($host, ['127.0.0.1', 'localhost'], true);

                if (!$isLocalHost) {
                    throw $stateException;
                }

                $code = (string) $request->query->get('code', '');
                if ($code === '') {
                    throw $stateException;
                }

                $accessToken = $client->getOAuth2Provider()->getAccessToken('authorization_code', [
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                ]);
            }

            if (!$accessToken) {
                throw new \Exception('No access token received from Google');
            }

            $googleUser = $client->fetchUserFromToken($accessToken);
            $email = $googleUser->getEmail();

            if (!$email) {
                throw new \Exception('No email from Google user');
            }

            $this->log("Google user authenticated", ['email' => $email]);

            // Find or create user
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->log("Creating new Google user", ['email' => $email]);
                $user = new User();
                $user->setEmail($email);
                $user->setUsername($googleUser->getName() ?? $email);
                $user->setRoles(['ROLE_STAFF']);
                $user->setPassword(bin2hex(random_bytes(16)));
                $user->setIsVerified(true);
                $user->setStatus('active');
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                $this->log("New user created", ['email' => $email, 'id' => $user->getId()]);
            } else {
                $needsFlush = false;
                if (!$user->isVerified()) {
                    $user->setIsVerified(true);
                    $needsFlush = true;
                }
                if (!in_array('ROLE_STAFF', $user->getRoles())) {
                    $roles = array_values($user->getRoles());
                    $roles[] = 'ROLE_STAFF';
                    $user->setRoles($roles);
                    $needsFlush = true;
                }
                if ($user->getStatus() !== 'active') {
                    $user->setStatus('active');
                    $needsFlush = true;
                }
                if ($needsFlush) {
                    $this->entityManager->flush();
                    $this->log("Existing user updated", ['email' => $email]);
                }
            }

            $this->log("Creating Passport for user", ['email' => $email]);
            
            // Let the configured user provider load the user by email.
            return new SelfValidatingPassport(new UserBadge($email));
        } catch (\Exception $e) {
            $this->log("Google authentication failed", ['error' => $e->getMessage()]);
            throw new AuthenticationException('Google authentication failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        $this->log("GoogleAuthenticator.onAuthenticationSuccess()", ['user' => $user ? $user->getEmail() : 'null', 'roles' => $user ? $user->getRoles() : []]);
        
        $dashboardUrl = $this->urlGenerator->generate('app_staff_dashboard');
        $response = new RedirectResponse($dashboardUrl);
        $this->log("Redirecting to staff dashboard", ['url' => $dashboardUrl]);
        
        return $response;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->log("Google authentication failed", ['error' => $exception->getMessage()]);
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('connect_google'));
    }
}

