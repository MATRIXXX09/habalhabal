<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GoogleAuthController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google', methods: ['GET'])]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        $redirectUri = $this->generateUrl('connect_google_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
        if (str_starts_with($redirectUri, 'https://127.0.0.1') || str_starts_with($redirectUri, 'https://localhost')) {
            $redirectUri = preg_replace('/^https:/', 'http:', $redirectUri);
        }

        $response = $clientRegistry->getClient('google')->redirect(
            ['profile', 'email'],
            ['redirect_uri' => $redirectUri]
        );

        return $response;
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(): Response
    {
        // The OAuth2 authenticator handles this endpoint
        // This action shouldn't be reached if authenticator works correctly
        return new Response();
    }
}
