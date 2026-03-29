<?php

namespace App\Controller;

use App\Entity\Rider;
use App\Entity\User;
use App\Form\RiderType;
use App\Repository\RiderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/riders')]
#[IsGranted('ROLE_ADMIN')]
class RiderController extends AbstractController
{
    #[Route('', name: 'app_admin_riders', methods: ['GET'])]
    public function index(RiderRepository $riderRepository): Response
    {
        return $this->render('admin/riders/index.html.twig', [
            'riders' => $riderRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_riders_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $rider = new Rider();
        $form = $this->createForm(RiderType::class, $rider);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Create a new User account for the rider
            $user = new User();
            $user->setEmail($form->get('email')->getData());
            $user->setRoles(['ROLE_RIDER']);
            $user->setIsVerified(true);
            
            // Hash the password
            $hashedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
            $user->setPassword($hashedPassword);
            
            // Link the user to the rider
            $rider->setUser($user);
            // Don't set createdBy for admin-created riders so they're visible to all staff
            $rider->setCreatedAt(new \DateTime());
            
            $entityManager->persist($user);
            $entityManager->persist($rider);
            $entityManager->flush();

            $this->addFlash('success', 'Rider created successfully');
            return $this->redirectToRoute('app_admin_riders');
        }

        return $this->render('admin/riders/new.html.twig', [
            'rider' => $rider,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_riders_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $rider = $entityManager->find(Rider::class, $id);
        
        if (!$rider) {
            $this->addFlash('error', 'Rider not found.');
            return $this->redirectToRoute('app_admin_riders');
        }
        
        $form = $this->createForm(RiderType::class, $rider, [
            'is_edit' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Rider updated successfully');
            return $this->redirectToRoute('app_admin_riders');
        }

        return $this->render('admin/riders/edit.html.twig', [
            'rider' => $rider,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_riders_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $rider = $entityManager->find(Rider::class, $id);
        
        if (!$rider) {
            $this->addFlash('error', 'Rider not found.');
            return $this->redirectToRoute('app_admin_riders');
        }
        
        if ($this->isCsrfTokenValid('delete'.$rider->getId(), $request->request->get('_token'))) {
            // Also delete the associated user account
            if ($rider->getUser()) {
                $entityManager->remove($rider->getUser());
            }
            $entityManager->remove($rider);
            $entityManager->flush();
            
            $this->addFlash('success', 'Rider deleted successfully');
        }
        return $this->redirectToRoute('app_admin_riders');
    }

    #[Route('/{id}/toggle-status', name: 'app_admin_riders_toggle_status', methods: ['POST'])]
    public function toggleStatus(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $rider = $entityManager->find(Rider::class, $id);
        
        if (!$rider) {
            $this->addFlash('error', 'Rider not found.');
            return $this->redirectToRoute('app_admin_riders');
        }
        
        $newStatus = $request->request->get('status', 'available');
        $validStatuses = ['available', 'on_delivery', 'on_leave', 'day_off'];
        
        if (!in_array($newStatus, $validStatuses)) {
            $newStatus = 'available';
        }

        $rider->setStatus($newStatus);
        $entityManager->flush();

        // Return JSON response for AJAX requests
        if ($this->isXmlHttpRequest()) {
            $statusLabels = [
                'available' => 'Available',
                'on_delivery' => 'On Delivery',
                'on_leave' => 'On Leave',
                'day_off' => 'Day Off'
            ];
            return $this->json([
                'status' => $newStatus,
                'statusLabel' => $statusLabels[$newStatus],
            ]);
        }

        // Add flash message based on new status
        $statusMessages = [
            'available' => 'is now available!',
            'on_delivery' => 'is now on delivery.',
            'on_leave' => 'is now on leave.',
            'day_off' => 'is now off for the day.'
        ];

        $this->addFlash('success', $rider->getFullName() . ' ' . $statusMessages[$newStatus]);
        return $this->redirectToRoute('app_admin_riders');
    }

    #[Route('/{id}/toggle-availability', name: 'app_admin_riders_toggle_availability', methods: ['POST'])]
    public function toggleAvailability(int $id, EntityManagerInterface $entityManager): Response
    {
        $rider = $entityManager->find(Rider::class, $id);
        
        if (!$rider) {
            return $this->json(['error' => 'Rider not found.'], 404);
        }
        
        $currentStatus = $rider->getStatus();
        
        if ($currentStatus === 'available') {
            $newStatus = 'on_delivery';
        } else {
            $newStatus = 'available';
        }
        
        $rider->setStatus($newStatus);
        $entityManager->flush();

        // Return JSON response for AJAX requests
        if ($this->isXmlHttpRequest()) {
            return $this->json([
                'available' => $newStatus === 'available',
                'status' => $newStatus,
            ]);
        }

        // Add flash message based on new status
        if ($newStatus === 'available') {
            $this->addFlash('success', $rider->getFullName() . ' is now available!');
        } else {
            $this->addFlash('info', $rider->getFullName() . ' is now on delivery.');
        }

        return $this->redirectToRoute('app_admin_riders');
    }

    private function isXmlHttpRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}