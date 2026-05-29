<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApiGoogleLoginController extends AbstractController
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private JWTTokenManagerInterface $jwtManager,
        private HttpClientInterface $httpClient
    ) {}

    private function getAllowedGoogleClientIds(): array
    {
        $configured = (string) ($_ENV['GOOGLE_CLIENT_IDS'] ?? $_SERVER['GOOGLE_CLIENT_IDS'] ?? '');

        $ids = array_filter(array_map('trim', explode(',', $configured)));

        foreach (['GOOGLE_CLIENT_ID', 'GOOGLE_WEB_CLIENT_ID', 'GOOGLE_ANDROID_CLIENT_ID', 'GOOGLE_IOS_CLIENT_ID'] as $key) {
            $value = (string) ($_ENV[$key] ?? $_SERVER[$key] ?? '');
            if ($value !== '') {
                $ids[] = $value;
            }
        }

        return array_values(array_unique($ids));
    }

    #[Route('/api/google-login', name: 'api_google_login', methods: ['POST'])]
    public function googleLogin(Request $request, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? null;
        $idToken = $data['idToken'] ?? $data['id_token'] ?? null;
        $redirectUri = $data['redirect_uri'] ?? $urlGenerator->generate('connect_google_check', [], UrlGeneratorInterface::ABSOLUTE_URL);

        if (!$code && !$idToken) {
            return $this->json(['success' => false, 'message' => 'Either authorization code or idToken is required'], 400);
        }

        try {
            $client = $this->clientRegistry->getClient('google');
            $email = null;
            $displayName = null;

            if ($idToken) {
                $tokenInfo = $this->httpClient->request('GET', 'https://oauth2.googleapis.com/tokeninfo', [
                    'query' => ['id_token' => $idToken],
                ])->toArray(false);

                $tokenAudience = (string) ($tokenInfo['aud'] ?? '');
                $tokenAuthorizedParty = (string) ($tokenInfo['azp'] ?? '');
                $allowedClientIds = $this->getAllowedGoogleClientIds();

                if ($tokenAudience === '') {
                    return $this->json(['success' => false, 'message' => 'Invalid Google token audience'], 400);
                }

                $audienceAccepted = empty($allowedClientIds) || in_array($tokenAudience, $allowedClientIds, true) || ($tokenAuthorizedParty !== '' && in_array($tokenAuthorizedParty, $allowedClientIds, true));

                if (!$audienceAccepted) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Invalid Google token audience',
                        'aud' => $tokenAudience,
                        'azp' => $tokenAuthorizedParty,
                    ], 400);
                }

                if (($tokenInfo['email_verified'] ?? 'false') !== 'true') {
                    return $this->json(['success' => false, 'message' => 'Google email is not verified'], 400);
                }

                $email = $tokenInfo['email'] ?? null;
                $displayName = $tokenInfo['name'] ?? null;
            } else {
                if (str_starts_with($redirectUri, 'https://127.0.0.1') || str_starts_with($redirectUri, 'https://localhost')) {
                    $redirectUri = preg_replace('/^https:/', 'http:', $redirectUri);
                }

                $accessToken = $client->getOAuth2Provider()->getAccessToken('authorization_code', [
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                ]);

                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();
                $displayName = $googleUser->getName();
            }

            if (!$email) {
                return $this->json(['success' => false, 'message' => 'No email returned from Google'], 400);
            }

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setUsername($displayName ?? $email);
                $user->setRoles(['ROLE_STAFF']);
                $user->setPassword(bin2hex(random_bytes(16)));
                $user->setIsVerified(true);
                $user->setStatus('active');
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            } else {
                $needsFlush = false;
                if (!$user->isVerified()) {
                    $user->setIsVerified(true);
                    $needsFlush = true;
                }
                if (!in_array('ROLE_STAFF', $user->getRoles())) {
                    $roles = $user->getRoles();
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
                }
            }

            $token = $this->jwtManager->create($user);

            return $this->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
