<?php

namespace App\Controller\Staff;

use App\Entity\Vehicle;
use App\Entity\Rider;
use App\Entity\User;
use App\Form\VehicleType;
use App\Repository\VehicleRepository;
use App\Repository\RiderRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\RealtimeBroadcaster;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff/vehicles', name: 'staff_vehicle_')]
#[IsGranted('ROLE_STAFF')]
class StaffVehicleController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(VehicleRepository $repository, RiderRepository $riderRepository): Response
    {
        $user = $this->getUser();
        $vehicles = $repository->findBy(['createdBy' => $user]);
        // Show riders created by this staff user or created by admin (createdBy IS NULL)
        $qb = $riderRepository->createQueryBuilder('r');
        $riders = $qb
            ->where('r.createdBy = :user OR r.createdBy IS NULL')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('staff/vehicle/index.html.twig', [
            'vehicles' => $vehicles,
            'riders' => $riders,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, RiderRepository $riderRepository, VehicleRepository $vehicleRepository, RealtimeBroadcaster $realtimeBroadcaster): Response
    {
        $vehicle = new Vehicle();
        $user = $this->getUser();
        
        // Get riders created by the current user or by admin
        $qb = $riderRepository->createQueryBuilder('r');
        $riders = $qb
            ->where('r.createdBy = :user OR r.createdBy IS NULL')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        // Get all vehicles created by the user with assigned riders to prevent duplication
        $allVehicles = $vehicleRepository->findBy(['createdBy' => $user]);
        $usedRiderIds = [];
        foreach ($allVehicles as $v) {
            if ($v->getRider()) {
                $usedRiderIds[] = $v->getRider()->getId();
            }
        }
        
        $form = $this->createForm(VehicleType::class, $vehicle, [
            'available_riders' => $riders,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle rider assignment from the hidden field
            $selectedRiderId = $form->get('riderHidden')->getData();
            if ($selectedRiderId) {
                $selectedRider = $riderRepository->find($selectedRiderId);
                if ($selectedRider && $selectedRider->getUser()) {
                    $vehicle->setRider($selectedRider->getUser());
                }
            }
            
            $vehicle->setCreatedBy($this->getUser());
            $vehicle->setCreatedAt(new \DateTimeImmutable());
            $em->persist($vehicle);
            $em->flush();
            $realtimeBroadcaster->broadcastDatabaseChanged([
                'source' => 'staff-vehicle-new',
                'vehicleId' => $vehicle->getId(),
            ]);

            $this->addFlash('success', 'Vehicle created successfully!');
            return $this->redirectToRoute('staff_vehicle_index');
        }

        return $this->render('staff/vehicle/new.html.twig', [
            'form' => $form,
            'riders' => $riders,
            'usedRiderIds' => $usedRiderIds,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Vehicle $vehicle): Response
    {
        $this->checkOwnership($vehicle);

        return $this->render('staff/vehicle/show.html.twig', [
            'vehicle' => $vehicle,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vehicle $vehicle, EntityManagerInterface $em, RiderRepository $riderRepository, RealtimeBroadcaster $realtimeBroadcaster): Response
    {
        $this->checkOwnership($vehicle);

        $user = $this->getUser();
        $qb = $riderRepository->createQueryBuilder('r');
        $riders = $qb
            ->where('r.createdBy = :user OR r.createdBy IS NULL')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        $form = $this->createForm(VehicleType::class, $vehicle, [
            'available_riders' => $riders,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $realtimeBroadcaster->broadcastDatabaseChanged([
                'source' => 'staff-vehicle-edit',
                'vehicleId' => $vehicle->getId(),
            ]);
            $this->addFlash('success', 'Vehicle updated successfully!');
            return $this->redirectToRoute('staff_vehicle_index');
        }

        return $this->render('staff/vehicle/edit.html.twig', [
            'form' => $form,
            'vehicle' => $vehicle,
            'riders' => $riders,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Vehicle $vehicle, EntityManagerInterface $em, RealtimeBroadcaster $realtimeBroadcaster): Response
    {
        $this->checkOwnership($vehicle);

        if ($this->isCsrfTokenValid('delete' . $vehicle->getId(), $request->request->get('_token'))) {
            $em->remove($vehicle);
            $em->flush();
            $realtimeBroadcaster->broadcastDatabaseChanged([
                'source' => 'staff-vehicle-delete',
                'vehicleId' => $vehicle->getId(),
            ]);
            $this->addFlash('success', 'Vehicle deleted successfully!');
        }

        return $this->redirectToRoute('staff_vehicle_index');
    }

    private function checkOwnership(Vehicle $vehicle): void
    {
        $currentUser = $this->getUser();
        $createdBy = $vehicle->getCreatedBy();
        
        if (!$createdBy instanceof User || !$currentUser instanceof User || $createdBy->getId() !== $currentUser->getId()) {
            throw $this->createAccessDeniedException('You can only manage your own vehicles.');
        }
    }
}
