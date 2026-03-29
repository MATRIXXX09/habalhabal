<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Entity\Rider;
use App\Form\VehicleType;
use App\Repository\VehicleRepository;
use App\Repository\RiderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/vehicles')]
#[IsGranted('ROLE_ADMIN')]
class AdminVehicleController extends AbstractController
{
    #[Route('', name: 'app_admin_vehicles')]
    public function index(VehicleRepository $vehicleRepository, RiderRepository $riderRepository): Response
    {
        return $this->render('admin/vehicles/index.html.twig', [
            'vehicles' => $vehicleRepository->findAll(),
            'riders' => $riderRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_vehicles_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, RiderRepository $riderRepository, VehicleRepository $vehicleRepository): Response
    {
        $vehicle = new Vehicle();
        $riders = $riderRepository->findAll();
        
        // Get all vehicles with assigned riders to prevent duplication
        $allVehicles = $vehicleRepository->findAll();
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
                    // Check if this rider is already assigned to another vehicle
                    $existingVehicle = $entityManager->getRepository(Vehicle::class)->findOneBy(['rider' => $selectedRider->getUser()]);
                    if ($existingVehicle) {
                        $this->addFlash('error', 'This rider is already assigned to another vehicle. Please select a different rider.');
                        return $this->redirectToRoute('app_admin_vehicles_new');
                    }
                    $vehicle->setRider($selectedRider->getUser());
                }
            }
            
            $entityManager->persist($vehicle);
            $entityManager->flush();

            $this->addFlash('success', 'Vehicle added successfully!');
            return $this->redirectToRoute('app_admin_vehicles');
        }

        return $this->render('admin/vehicles/new.html.twig', [
            'form' => $form->createView(),
            'riders' => $riders,
            'usedRiderIds' => $usedRiderIds,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_vehicles_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager, RiderRepository $riderRepository): Response
    {
        $riders = $riderRepository->findAll();
        
        $form = $this->createForm(VehicleType::class, $vehicle, [
            'available_riders' => $riders,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle rider assignment from the hidden field
            $selectedRiderId = $form->get('riderHidden')->getData();
            $originalRider = $vehicle->getRider();
            
            if ($selectedRiderId) {
                $selectedRider = $riderRepository->find($selectedRiderId);
                if ($selectedRider && $selectedRider->getUser()) {
                    // Check if this rider is already assigned to another vehicle (but not this one)
                    $existingVehicle = $entityManager->getRepository(Vehicle::class)->findOneBy(['rider' => $selectedRider->getUser()]);
                    if ($existingVehicle && $existingVehicle->getId() !== $vehicle->getId()) {
                        $this->addFlash('error', 'This rider is already assigned to another vehicle. Please select a different rider.');
                        return $this->render('admin/vehicles/edit.html.twig', [
                            'vehicle' => $vehicle,
                            'form' => $form->createView(),
                            'riders' => $riders,
                        ]);
                    }
                    $vehicle->setRider($selectedRider->getUser());
                }
            } else {
                // Clear rider if no selection
                $vehicle->setRider(null);
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Vehicle updated successfully!');
            return $this->redirectToRoute('app_admin_vehicles');
        }

        return $this->render('admin/vehicles/edit.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form->createView(),
            'riders' => $riders,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_vehicles_delete', methods: ['POST'])]
    public function delete(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$vehicle->getId(), $request->request->get('_token'))) {
            $entityManager->remove($vehicle);
            $entityManager->flush();
            $this->addFlash('success', 'Vehicle deleted successfully!');
        }

        return $this->redirectToRoute('app_admin_vehicles');
    }

    #[Route('/{id}/toggle-availability', name: 'app_admin_vehicles_toggle_availability', methods: ['POST'])]
    public function toggleAvailability(Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        $vehicle->setIsAvailable(!$vehicle->isAvailable());
        $entityManager->flush();

        return $this->json(['available' => $vehicle->isAvailable()]);
    }
}

