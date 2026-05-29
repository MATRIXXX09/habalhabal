<?php

namespace App\Controller\Staff;

use App\Entity\Rider;
use App\Entity\User;
use App\Form\RiderType;
use App\Repository\RiderRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\RealtimeBroadcaster;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/staff/riders', name: 'staff_rider_')]
#[IsGranted('ROLE_STAFF')]
class StaffRiderController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(RiderRepository $repository): Response
    {
        $user = $this->getUser();
        
        // Get riders created by current staff AND riders created by admins (createdBy = null)
        // This way staff can see their own riders plus any riders assigned by admins
        $qb = $repository->createQueryBuilder('r');
        $riders = $qb
            ->where('r.createdBy = :user OR r.createdBy IS NULL')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('staff/rider/index.html.twig', [
            'riders' => $riders,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, RealtimeBroadcaster $realtimeBroadcaster): Response
    {
        $rider = new Rider();
        $form = $this->createForm(RiderType::class, $rider);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Create a new User account for the rider
            $user = new User();
            $user->setEmail($form->get('email')->getData());
            $user->setRoles(['ROLE_RIDER']);
            $user->setIsVerified(true);
            $user->setCreatedBy($this->getUser());
            $user->setCreatedAt(new \DateTime());
            
            // Hash the password
            $hashedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
            $user->setPassword($hashedPassword);
            
            // Link the user to the rider
            $rider->setUser($user);
            $rider->setCreatedBy($this->getUser());
            $rider->setCreatedAt(new \DateTime());
            
            $em->persist($user);
            $em->persist($rider);
            $em->flush();
            $realtimeBroadcaster->broadcastDatabaseChanged([
                'source' => 'staff-rider-new',
                'riderId' => $rider->getId(),
            ]);

            $this->addFlash('success', 'Rider created successfully!');
            return $this->redirectToRoute('staff_rider_index');
        }

        return $this->render('staff/rider/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Rider $rider): Response
    {
        $this->checkOwnership($rider);

        return $this->render('staff/rider/show.html.twig', [
            'rider' => $rider,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Rider $rider, EntityManagerInterface $em, RealtimeBroadcaster $realtimeBroadcaster): Response
    {
        $this->checkOwnership($rider);

        $form = $this->createForm(RiderType::class, $rider);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $realtimeBroadcaster->broadcastDatabaseChanged([
                'source' => 'staff-rider-edit',
                'riderId' => $rider->getId(),
            ]);
            $this->addFlash('success', 'Rider updated successfully!');
            return $this->redirectToRoute('staff_rider_index');
        }

        return $this->render('staff/rider/edit.html.twig', [
            'form' => $form,
            'rider' => $rider,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Rider $rider, EntityManagerInterface $em, RealtimeBroadcaster $realtimeBroadcaster): Response
    {
        $this->checkOwnership($rider);

        if ($this->isCsrfTokenValid('delete' . $rider->getId(), $request->request->get('_token'))) {
            $em->remove($rider);
            $em->flush();
            $realtimeBroadcaster->broadcastDatabaseChanged([
                'source' => 'staff-rider-delete',
                'riderId' => $rider->getId(),
            ]);
            $this->addFlash('success', 'Rider deleted successfully!');
        }

        return $this->redirectToRoute('staff_rider_index');
    }

    #[Route('/{id}/toggle-status', name: 'toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, Rider $rider, EntityManagerInterface $em, RealtimeBroadcaster $realtimeBroadcaster): Response
    {
        $this->checkOwnership($rider);

        if (!$this->isCsrfTokenValid('toggle_status_' . $rider->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $newStatus = $request->request->get('status', 'available');
        $validStatuses = ['available', 'on_delivery', 'on_leave', 'day_off'];
        
        if (!in_array($newStatus, $validStatuses)) {
            $newStatus = 'available';
        }

        $rider->setStatus($newStatus);
        $em->flush();
        $realtimeBroadcaster->broadcastDatabaseChanged([
            'source' => 'staff-rider-status',
            'riderId' => $rider->getId(),
            'status' => $newStatus,
        ]);

        // Return JSON response for AJAX requests
        if ($request->isXmlHttpRequest()) {
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
        return $this->redirectToRoute('staff_rider_index');
    }

    private function checkOwnership(Rider $rider): void
    {
        $currentUser = $this->getUser();
        // Allow access if:
        // 1. The rider was created by the current staff user, OR
        // 2. The rider was created by admin (createdBy is null) so it's available to all staff
        $isOwnedByStaff = $rider->getCreatedBy()?->getId() === $currentUser->getId();
        $isAdminCreated = $rider->getCreatedBy() === null;
        
        if (!($currentUser instanceof User && ($isOwnedByStaff || $isAdminCreated))) {
            throw $this->createAccessDeniedException('You can only manage your own riders or admin-assigned riders.');
        }
    }
}
