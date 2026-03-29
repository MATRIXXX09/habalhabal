<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/profile')]
#[IsGranted('ROLE_ADMIN')]
class UserProfileController extends AbstractController
{
    #[Route('', name: 'app_profile_view')]
    public function view(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('User not found');
        }

        return $this->render('profile/view.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/change-password', name: 'app_profile_change_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('User not found');
        }

        $currentPassword = $request->request->get('current_password');
        $newPassword = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');
        $csrfToken = $request->request->get('_token');

        $error = null;
        $success = false;

        if ($request->isMethod('POST')) {
            // Verify CSRF token
            if (!$this->isCsrfTokenValid('change_password', $csrfToken)) {
                $error = 'Invalid CSRF token. Please try again.';
            }
            // Verify current password
            elseif (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $error = 'Current password is incorrect.';
            }
            // Check passwords match
            elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match.';
            }
            // Check password length
            elseif (strlen($newPassword) < 6) {
                $error = 'Password must be at least 6 characters long.';
            }
            // Check same as current
            elseif ($passwordHasher->isPasswordValid($user, $newPassword)) {
                $error = 'New password must be different from current password.';
            }
            else {
                // Update password
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $entityManager->persist($user);
                $entityManager->flush();
                
                // Refresh the security token so the user session stays active with new password
                $token = $this->container->get('security.token_storage')->getToken();
                if ($token) {
                    $token->setUser($user);
                }
                
                $success = true;
                $this->addFlash('success', 'Password changed successfully!');
            }
        }

        return $this->render('profile/change_password.html.twig', [
            'user' => $user,
            'error' => $error,
            'success' => $success,
        ]);
    }
}
