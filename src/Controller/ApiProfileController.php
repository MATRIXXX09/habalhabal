<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ApiProfileController extends AbstractController
{
    #[Route('/api/profile', name: 'api_profile', methods: ['GET'])]
    public function profile(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
                'status' => $user->getStatus(),
                'isVerified' => $user->isVerified(),
                'walletBalance' => (float) $user->getWalletBalance(),
                'createdAt' => $user->getCreatedAt()?->format(DATE_ATOM),
            ],
        ]);
    }

    #[Route('/api/profile', name: 'api_profile_update', methods: ['PATCH', 'POST'])]
    public function updateProfile(
        Request $request,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'No data provided',
                ], 400);
            }

            // Update phone number if provided
            if (isset($data['phoneNumber'])) {
                $user->setUsername($data['phoneNumber']);
            }

            $em->persist($user);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'username' => $user->getUsername(),
                    'roles' => $user->getRoles(),
                    'status' => $user->getStatus(),
                    'isVerified' => $user->isVerified(),
                    'walletBalance' => (float) $user->getWalletBalance(),
                    'createdAt' => $user->getCreatedAt()?->format(DATE_ATOM),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage(),
            ], 500);
        }
    }
}