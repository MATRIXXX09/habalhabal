<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MessageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    ) {
    }

    #[Route('/send-contact-message', name: 'app_send_contact_message', methods: ['POST'])]
    public function sendContactMessage(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validate input
            if (!isset($data['email']) || !isset($data['subject']) || !isset($data['message'])) {
                return new JsonResponse(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
            }

            // Get the first admin user
            $admin = $this->userRepository->findOneBy(['roles' => 'ROLE_ADMIN']);
            if (!$admin) {
                // If no admin found, create a default or get any admin
                $admins = $this->userRepository->createQueryBuilder('u')
                    ->where('u.roles LIKE :role')
                    ->setParameter('role', '%ROLE_ADMIN%')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getResult();

                if (empty($admins)) {
                    return new JsonResponse(['error' => 'No admin user found'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                $admin = $admins[0];
            }

            // Create message
            $message = new Message();
            $message->setSenderEmail($data['email']);
            $message->setSubject($data['subject']);
            $message->setMessage($data['message']);
            $message->setAdmin($admin);

            $this->entityManager->persist($message);
            $this->entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Message sent successfully']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/admin/messages', name: 'app_admin_messages')]
    public function messages(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $admin = $this->getUser();
        if (!$admin instanceof User) {
            throw $this->createAccessDeniedException('Not an admin');
        }

        $messages = $this->entityManager->getRepository(Message::class)->findAllMessagesForAdmin($admin);

        return $this->render('admin/messages.html.twig', [
            'messages' => $messages,
        ]);
    }

    #[Route('/admin/messages/{id}/mark-read', name: 'app_admin_message_mark_read', methods: ['POST'])]
    public function markMessageAsRead(Message $message): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $admin = $this->getUser();
        if ($message->getAdmin()->getId() !== $admin->getId()) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $message->markAsRead();
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
