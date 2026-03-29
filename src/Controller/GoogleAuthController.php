<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class GoogleAuthController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google', methods: ['GET'])]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('google')->redirect([
            'profile', 'email'
        ]);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry, EntityManagerInterface $entityManager): Response
    {
        // Handle redirect from Google OAuth provider
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // This should be handled by GoogleAuthenticator
        // Just redirect to dashboard
        return $this->redirectToRoute('app_home');
    }
}
