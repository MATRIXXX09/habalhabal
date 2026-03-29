<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ShipmentRepository;
use App\Repository\ComplaintRepository;
use App\Repository\VehicleRepository;
use App\Repository\RiderRepository;
use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
}
