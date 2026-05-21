<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Complaint;
use App\Entity\Shipment;
use App\Entity\Vehicle;
use App\Entity\Rider;
use App\Entity\ActivityLog;
use App\Entity\Message;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\ShipmentRepository;
use App\Repository\ComplaintRepository;
use App\Repository\ActivityLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_index')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/profile', name: 'app_admin_profile')]
    public function showProfile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('User not found');
        }

        return $this->render('admin/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        ShipmentRepository $shipmentRepository,
        ComplaintRepository $complaintRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Get complaint statistics
        $complaints = $complaintRepository->findAll();
        $pendingComplaints = array_filter($complaints, fn($c) => $c->getStatus() === 'pending');
        $resolvedComplaints = array_filter($complaints, fn($c) => $c->getStatus() === 'resolved');

        // Get vehicle statistics
        $vehicleRepository = $entityManager->getRepository(Vehicle::class);
        $vehicles = $vehicleRepository->findAll();
        $availableVehicles = array_filter($vehicles, fn($v) => $v->isAvailable());
        $inUseVehicles = array_filter($vehicles, fn($v) => !$v->isAvailable());

        // Get user role statistics
        $allUsers = $userRepository->findAll();
        $staffUsers = array_filter($allUsers, function($user) {
            return in_array('ROLE_STAFF', $user->getRoles());
        });
        
        $riderRepository = $entityManager->getRepository(Rider::class);
        $riders = $riderRepository->findAll();

        // Get shipment statistics by status
        $shipmentRepo = $entityManager->getRepository(Shipment::class);
        $allShipments = $shipmentRepo->findAll();
        $pendingShipments = array_filter($allShipments, fn($s) => $s->getStatus() === 'pending');
        $inTransitShipments = array_filter($allShipments, fn($s) => $s->getStatus() === 'in_transit');
        $deliveredShipments = array_filter($allShipments, fn($s) => $s->getStatus() === 'delivered');

        // Get user status statistics
        $activeUsers = $userRepository->createQueryBuilder('u')
            ->where("u.status = 'active'")
            ->getQuery()
            ->getResult();
        
        $disabledUsers = $userRepository->createQueryBuilder('u')
            ->where("u.status = 'disabled'")
            ->getQuery()
            ->getResult();

        // Get recent activity logs
        $activityLogRepository = $entityManager->getRepository(ActivityLog::class);
        $recentActivities = $activityLogRepository->createQueryBuilder('a')
            ->orderBy('a.dateTime', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Get unread message count
        $messageRepository = $entityManager->getRepository(\App\Entity\Message::class);
        $unreadMessageCount = $messageRepository->countUnreadMessagesForAdmin($this->getUser());

        return $this->render('admin/dashboard.html.twig', [
            // User statistics
            'users' => $userRepository->count([]),
            'active_users' => count($activeUsers),
            'disabled_users' => count($disabledUsers),
            'staff_count' => count($staffUsers),
            'riders_count' => count($riders),
            
            // Shipment statistics
            'shipments' => count($allShipments),
            'shipments_pending' => count($pendingShipments),
            'shipments_in_transit' => count($inTransitShipments),
            'shipments_delivered' => count($deliveredShipments),
            
            // Complaint statistics
            'complaints_total' => count($complaints),
            'complaints_pending' => count($pendingComplaints),
            'complaints_done' => count($resolvedComplaints),
            
            // Vehicle statistics
            'vehicles' => count($vehicles),
            'vehicles_available' => count($availableVehicles),
            'vehicles_in_use' => count($inUseVehicles),
            
            // Recent activities
            'recent_activities' => $recentActivities,
            
            // Message notifications
            'unreadMessageCount' => $unreadMessageCount,
        ]);
    }

    #[Route('/users', name: 'app_admin_users')]
    public function users(EntityManagerInterface $entityManager): Response
    {
        // Clear the entity manager to ensure fresh data
        $entityManager->clear();
        
        // Get fresh data from database - exclude archived users
        $users = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where("u.status != 'archived'")
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/messages', name: 'app_admin_messages')]
    public function messages(EntityManagerInterface $entityManager): Response
    {
        $messageRepository = $entityManager->getRepository(Message::class);
        $complaintRepository = $entityManager->getRepository(Complaint::class);
        
        $messages = $messageRepository->findAll();
        $complaints = $complaintRepository->findAll();

        return $this->render('admin/messages.html.twig', [
            'messages' => $messages,
            'complaints' => $complaints,
        ]);
    }

    #[Route('/messages/{id}/mark-read', name: 'app_admin_messages_mark_read', methods: ['POST'])]
    public function markMessageAsRead(Message $message, EntityManagerInterface $entityManager): Response
    {
        $message->markAsRead();
        $entityManager->persist($message);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/messages/{id}/delete', name: 'app_admin_messages_delete', methods: ['POST'])]
    public function deleteMessage(Message $message, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($message);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/complaints/{id}/delete', name: 'app_admin_complaints_delete', methods: ['POST'])]
    public function deleteComplaint(Complaint $complaint, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($complaint);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/messages/{id}/update-status', name: 'app_admin_messages_update_status', methods: ['POST'])]
    public function updateMessageStatus(Message $message, Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        $status = $data['status'] ?? null;

        if (!$status) {
            return $this->json(['error' => 'Status required'], Response::HTTP_BAD_REQUEST);
        }

        $message->setStatus($status);
        $entityManager->persist($message);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/messages/{id}/reply', name: 'app_admin_messages_reply', methods: ['POST'])]
    public function replyToMessage(Message $message, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $data = json_decode($request->getContent(), true);
        $reply = $data['reply'] ?? null;

        if (!$reply) {
            return $this->json(['error' => 'Reply text required'], Response::HTTP_BAD_REQUEST);
        }

        $message->setReply($reply);
        $message->setRepliedAt(new \DateTime());
        $message->setStatus('resolved');
        $entityManager->persist($message);
        $entityManager->flush();

        // Send reply email to sender
        if ($message->getSenderEmail()) {
            try {
                $fromEmail = getenv('MAILER_FROM') ?: 'manyhandy594@gmail.com';
                $emailMessage = (new Email())
                    ->from($fromEmail)
                    ->to($message->getSenderEmail())
                    ->replyTo($fromEmail)
                    ->subject("Re: " . $message->getSubject())
                    ->html("<html>
                        <body style='font-family: Arial, sans-serif; color: #333;'>
                            <div style='background: linear-gradient(135deg, #1a1a1a 0%, #15803d 100%); color: white; padding: 20px; border-radius: 5px 5px 0 0;'>
                                <h2 style='margin: 0; font-size: 24px;'>We've Replied to Your Message</h2>
                            </div>
                            <div style='padding: 20px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 0 0 5px 5px;'>
                                <p>Hello,</p>
                                <p>Thank you for contacting us. We have reviewed your message <strong>\"" . htmlspecialchars($message->getSubject()) . "\"</strong> and here is our response:</p>
                                <hr style='border: none; border-top: 2px solid #15803d; margin: 20px 0;'>
                                <div style='background: white; padding: 15px; border-left: 4px solid #15803d; margin: 20px 0;'>
                                    <p>" . nl2br(htmlspecialchars($reply)) . "</p>
                                </div>
                                <hr style='border: none; border-top: 2px solid #15803d; margin: 20px 0;'>
                                <p>If you have any further questions, feel free to contact us again.</p>
                                <p>Best regards,<br><strong>Our Support Team</strong></p>
                            </div>
                        </body>
                    </html>");
                
                $mailer->send($emailMessage);
            } catch (\Throwable $e) {
                // Log error but still return success since message was saved
                error_log('Failed to send reply email: ' . $e->getMessage());
            }
        }

        return $this->json(['success' => true]);
    }

    #[Route('/complaints/{id}/resolve', name: 'app_admin_complaints_resolve', methods: ['POST'])]
    public function resolveComplaint(Complaint $complaint, EntityManagerInterface $entityManager): Response
    {
        $complaint->setStatus('resolved');
        $entityManager->persist($complaint);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/archives', name: 'app_admin_archives')]
    public function archives(UserRepository $userRepository): Response
    {
        $archivedUsers = $userRepository->createQueryBuilder('u')
            ->where("u.status = 'archived'")
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/archives.html.twig', [
            'users' => $archivedUsers,
        ]);
    }

    #[Route('/shipments', name: 'app_admin_shipments')]
    public function shipments(ShipmentRepository $shipmentRepository): Response
    {
        return $this->render('admin/shipments.html.twig', [
            'shipments' => $shipmentRepository->findAll(),
        ]);
    }

    #[Route('/users/new', name: 'app_admin_users_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function newUser(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has('plainPassword')) {
                $plainPassword = $form->get('plainPassword')->getData();
                if ($plainPassword) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }
            }
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'User created successfully!');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/users/{id}/edit', name: 'app_admin_users_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function editUser(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Prevent editing of own account
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'You cannot edit your own account through this interface.');
            return $this->redirectToRoute('app_admin_users');
        }

        // Get current roles before any changes
        $currentRoles = $user->getRoles();
        
        $form = $this->createForm(UserType::class, $user, [
            'require_password' => false,
            'default_roles' => $currentRoles
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Store the form data before clearing the entity manager
                $formData = [
                    'roles' => $form->get('roles')->getData(),
                    'isVerified' => $form->get('isVerified')->getData(),
                    'password' => $form->has('plainPassword') ? $form->get('plainPassword')->getData() : null
                ];
                
                // Clear and refresh entity manager
                $entityManager->clear();
                $user = $entityManager->find(User::class, $user->getId());
                
                // Apply the changes from the form
                if ($formData['password']) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $formData['password']);
                    $user->setPassword($hashedPassword);
                }
                
                // Set verification status
                $user->setIsVerified($formData['isVerified']);
                
                // Set roles
                if (in_array('ROLE_RIDER', (array)$formData['roles'])) {
                    $user->setRoles(['ROLE_RIDER']);
                } else {
                    $user->setRoles((array)$formData['roles']);
                }
                
                // Persist and flush changes
                $entityManager->persist($user);
                $entityManager->flush();
                
                $this->addFlash('success', 'User updated successfully.');
                return $this->redirectToRoute('app_admin_users');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while updating the user. Please try again.');
                return $this->redirectToRoute('app_admin_users');
            }
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'app_admin_users_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteUser(
        Request $request,
        int $id,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $entityManager->find(User::class, $id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'You cannot delete your own account.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            try {
                // Check if this user is associated with a rider and delete the rider first
                $riderRepository = $entityManager->getRepository(Rider::class);
                $rider = $riderRepository->findOneBy(['user' => $user]);
                if ($rider) {
                    $entityManager->remove($rider);
                }
                
                // Now delete the user
                $entityManager->remove($user);
                $entityManager->flush();
                $this->addFlash('success', 'User deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while deleting the user: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/disable', name: 'app_admin_users_disable', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function disableUser(
        Request $request,
        int $id,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $entityManager->find(User::class, $id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'You cannot disable your own account.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($this->isCsrfTokenValid('disable'.$user->getId(), $request->request->get('_token'))) {
            try {
                $user->disable();
                $entityManager->flush();
                $this->addFlash('success', sprintf('Staff account %s has been disabled.', $user->getEmail()));
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while disabling the account.');
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/archive', name: 'app_admin_users_archive', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function archiveUser(
        Request $request,
        int $id,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $entityManager->find(User::class, $id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'You cannot archive your own account.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($this->isCsrfTokenValid('archive'.$user->getId(), $request->request->get('_token'))) {
            try {
                $user->archive();
                $entityManager->flush();
                $this->addFlash('success', sprintf('Staff account %s has been archived.', $user->getEmail()));
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while archiving the account.');
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/reactivate', name: 'app_admin_users_reactivate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reactivateUser(
        Request $request,
        int $id,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $entityManager->find(User::class, $id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($this->isCsrfTokenValid('reactivate'.$user->getId(), $request->request->get('_token'))) {
            try {
                $user->reactivate();
                $entityManager->flush();
                $this->addFlash('success', sprintf('Staff account %s has been reactivated.', $user->getEmail()));
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while reactivating the account.');
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

}