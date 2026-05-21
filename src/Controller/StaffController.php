<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Message;
use App\Entity\Complaint;
use App\Repository\ShipmentRepository;
use App\Repository\ComplaintRepository;
use App\Repository\VehicleRepository;
use App\Repository\RiderRepository;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff')]
#[IsGranted('ROLE_STAFF')]
class StaffController extends AbstractController
{
    #[Route('/profile', name: 'app_staff_profile')]
    public function showProfile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('User not found');
        }

        return $this->render('staff/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/dashboard', name: 'app_staff_dashboard')]
    public function dashboard(
        ShipmentRepository $shipmentRepository,
        ComplaintRepository $complaintRepository,
        VehicleRepository $vehicleRepository,
        RiderRepository $riderRepository,
        BookingRepository $bookingRepository
    ): Response {
        $user = $this->getUser();
        
        // Get records created by the current staff member
        $myShipments = $shipmentRepository->findBy(['createdBy' => $user], ['createdAt' => 'DESC'], 10);
        $myComplaints = $complaintRepository->findBy(['createdBy' => $user], ['createdAt' => 'DESC'], 10);
        $myVehicles = $vehicleRepository->findBy(['createdBy' => $user], ['createdAt' => 'DESC'], 10);
        $myRiders = $riderRepository->findBy([], ['createdAt' => 'DESC'], 10);
        $myBookings = $bookingRepository->findBy(['createdBy' => $user], ['createdAt' => 'DESC'], 10);

        // Get total counts
        $totalShipments = $shipmentRepository->count(['createdBy' => $user]);
        $totalComplaints = $complaintRepository->count(['createdBy' => $user]);
        $totalVehicles = $vehicleRepository->count(['createdBy' => $user]);
        $totalRiders = $riderRepository->count([]);
        $totalBookings = $bookingRepository->count(['createdBy' => $user]);

        return $this->render('staff/dashboard.html.twig', [
            'myShipments' => $myShipments,
            'myComplaints' => $myComplaints,
            'myVehicles' => $myVehicles,
            'myRiders' => $myRiders,
            'myBookings' => $myBookings,
            'totalShipments' => $totalShipments,
            'totalComplaints' => $totalComplaints,
            'totalVehicles' => $totalVehicles,
            'totalRiders' => $totalRiders,
            'totalBookings' => $totalBookings,
        ]);
    }

    #[Route('/messages', name: 'staff_messages')]
    public function messages(EntityManagerInterface $entityManager): Response
    {
        $messageRepository = $entityManager->getRepository(Message::class);
        $complaintRepository = $entityManager->getRepository(Complaint::class);
        
        $messages = $messageRepository->findAll();
        $complaints = $complaintRepository->findAll();

        return $this->render('staff/messages.html.twig', [
            'messages' => $messages,
            'complaints' => $complaints,
        ]);
    }

    #[Route('/messages/{id}/mark-read', name: 'staff_messages_mark_read', methods: ['POST'])]
    public function markMessageAsRead(Message $message, EntityManagerInterface $entityManager): Response
    {
        $message->markAsRead();
        $entityManager->persist($message);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/messages/{id}/delete', name: 'staff_messages_delete', methods: ['POST'])]
    public function deleteMessage(Message $message, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($message);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/complaints/{id}/delete', name: 'staff_complaints_delete', methods: ['POST'])]
    public function deleteComplaint(Complaint $complaint, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($complaint);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/messages/{id}/update-status', name: 'staff_messages_update_status', methods: ['POST'])]
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

    #[Route('/messages/{id}/reply', name: 'staff_messages_reply', methods: ['POST'])]
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

    #[Route('/complaints/{id}/resolve', name: 'staff_complaints_resolve', methods: ['POST'])]
    public function resolveComplaint(Complaint $complaint, EntityManagerInterface $entityManager): Response
    {
        $complaint->setStatus('resolved');
        $entityManager->persist($complaint);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }
}
