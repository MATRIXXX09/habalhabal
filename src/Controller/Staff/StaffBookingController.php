<?php

namespace App\Controller\Staff;

use App\Entity\Booking;
use App\Entity\User;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Repository\RiderRepository;
use App\Service\DriverAvailabilityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff/bookings', name: 'staff_booking_')]
#[IsGranted('ROLE_STAFF')]
class StaffBookingController extends AbstractController
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private DriverAvailabilityService $driverAvailabilityService,
        private RiderRepository $riderRepository,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $bookings = $this->bookingRepository->findAllOrdered();

        return $this->render('staff/booking/index.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Save the booking directly to database
            $booking->setCustomer($this->getUser());
            $booking->setCreatedBy($this->getUser());
            $booking->setStatus('pending');
            $em->persist($booking);
            $em->flush();

            $this->addFlash('success', 'Booking created successfully!');
            return $this->redirectToRoute('staff_booking_index');
        }

        return $this->render('staff/booking/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Booking $booking): Response
    {
        $this->checkOwnership($booking);

        // Get available drivers if booking is still pending
        $availableDrivers = [];
        if ($booking->getStatus() === 'pending') {
            $pickupTime = $booking->getRequestedPickupTime();
            $deliveryTime = $booking->getRequestedDeliveryTime();
            
            if (!$deliveryTime) {
                $deliveryTime = new \DateTime($pickupTime->format('Y-m-d H:i:s'));
                $deliveryTime->modify('+2 hours');
            }

            $availableDrivers = $this->driverAvailabilityService->getAvailableDrivers(
                $pickupTime,
                $deliveryTime
            );

            // Get driver stats for each available driver
            $driversWithStats = [];
            foreach ($availableDrivers as $driver) {
                $driversWithStats[] = [
                    'driver' => $driver,
                    'stats' => $this->driverAvailabilityService->getDriverStats($driver),
                ];
            }
            $availableDrivers = $driversWithStats;
        }

        return $this->render('staff/booking/show.html.twig', [
            'booking' => $booking,
            'availableDrivers' => $availableDrivers,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Booking $booking, EntityManagerInterface $em): Response
    {
        $this->checkOwnership($booking);

        // Don't allow editing if booking is already assigned or completed
        if (in_array($booking->getStatus(), ['assigned', 'in_transit', 'completed', 'cancelled'])) {
            $this->addFlash('error', 'Cannot edit a booking in this status.');
            return $this->redirectToRoute('staff_booking_show', ['id' => $booking->getId()]);
        }

        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $booking->setUpdatedAt(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Booking updated successfully!');
            return $this->redirectToRoute('staff_booking_show', ['id' => $booking->getId()]);
        }

        return $this->render('staff/booking/edit.html.twig', [
            'form' => $form,
            'booking' => $booking,
        ]);
    }

    #[Route('/{id}/assign-driver/{driverId}', name: 'assign_driver', methods: ['POST'])]
    public function assignDriver(Request $request, Booking $booking, int $driverId, EntityManagerInterface $em): Response
    {
        $this->checkOwnership($booking);

        if (!$this->isCsrfTokenValid('assign_driver_' . $booking->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }

        if ($booking->getStatus() !== 'pending') {
            return new JsonResponse(['error' => 'Booking is not in pending status'], Response::HTTP_BAD_REQUEST);
        }

        $driver = $this->riderRepository->find($driverId);
        if (!$driver) {
            return new JsonResponse(['error' => 'Driver not found'], Response::HTTP_NOT_FOUND);
        }

        // Verify driver is still available
        $pickupTime = $booking->getRequestedPickupTime();
        $deliveryTime = $booking->getRequestedDeliveryTime();
        
        if (!$deliveryTime) {
            $deliveryTime = new \DateTime($pickupTime->format('Y-m-d H:i:s'));
            $deliveryTime->modify('+2 hours');
        }

        if (!$this->driverAvailabilityService->isDriverAvailableForTimeSlot($driver, $pickupTime, $deliveryTime)) {
            return new JsonResponse(['error' => 'Driver is no longer available'], Response::HTTP_CONFLICT);
        }

        $booking->setAssignedDriver($driver);
        $booking->setAssignedAt(new \DateTime());
        $booking->setStatus('confirmed');
        $booking->setUpdatedAt(new \DateTime());

        $em->flush();

        $this->addFlash('success', sprintf('Booking assigned to driver %s successfully!', $driver->getFirstName()));

        return $this->redirectToRoute('staff_booking_show', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(Request $request, Booking $booking, EntityManagerInterface $em): Response
    {
        $this->checkOwnership($booking);

        if (!$this->isCsrfTokenValid('cancel_' . $booking->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }

        if (in_array($booking->getStatus(), ['completed', 'cancelled'])) {
            return new JsonResponse(['error' => 'Cannot cancel this booking'], Response::HTTP_BAD_REQUEST);
        }

        $booking->setStatus('cancelled');
        $booking->setUpdatedAt(new \DateTime());
        $booking->setCancellationReason($request->request->get('reason', ''));

        $em->flush();

        $this->addFlash('success', 'Booking cancelled successfully!');

        return $this->redirectToRoute('staff_booking_index');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Booking $booking, EntityManagerInterface $em): Response
    {
        $this->checkOwnership($booking);

        if (!$this->isCsrfTokenValid('delete_' . $booking->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }

        if ($booking->getStatus() !== 'pending') {
            $this->addFlash('error', 'Can only delete pending bookings.');
            return $this->redirectToRoute('staff_booking_show', ['id' => $booking->getId()]);
        }

        $em->remove($booking);
        $em->flush();

        $this->addFlash('success', 'Booking deleted successfully!');
        return $this->redirectToRoute('staff_booking_index');
    }

    /**
     * AJAX endpoint to check driver availability and get estimates
     */
    #[Route('/api/check-availability', name: 'check_availability', methods: ['POST'])]
    public function checkAvailability(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['pickupTime'], $data['deliveryTime'], $data['distance'], $data['parcelType'])) {
                return new JsonResponse(
                    ['error' => 'Missing required fields'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $pickupTime = new \DateTime($data['pickupTime']);
            $deliveryTime = new \DateTime($data['deliveryTime']);
            $distance = (float) $data['distance'];
            $parcelType = $data['parcelType'];

            // Get available drivers
            $availableDrivers = $this->driverAvailabilityService->getAvailableDrivers(
                $pickupTime,
                $deliveryTime
            );

            // Calculate estimates
            $estimatedFare = $this->driverAvailabilityService->calculateEstimatedFare($distance, $parcelType);
            $estimatedDuration = $this->driverAvailabilityService->calculateEstimatedDuration($distance);

            return new JsonResponse([
                'availableCount' => count($availableDrivers),
                'estimatedFare' => $estimatedFare,
                'estimatedDuration' => $estimatedDuration,
                'distance' => $distance,
                'drivers' => array_map(function ($driver) {
                    return [
                        'id' => $driver->getId(),
                        'name' => $driver->getFirstName() . ' ' . $driver->getLastName(),
                        'vehicleType' => $driver->getVehicleType(),
                        'plateNumber' => $driver->getPlateNumber(),
                        'stats' => $this->driverAvailabilityService->getDriverStats($driver),
                    ];
                }, $availableDrivers),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Error checking availability: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function checkOwnership(Booking $booking): void
    {
        $currentUser = $this->getUser();
        $createdBy = $booking->getCreatedBy();

        if (!$createdBy instanceof User || !$currentUser instanceof User || $createdBy->getId() !== $currentUser->getId()) {
            throw $this->createAccessDeniedException('You can only manage your own bookings.');
        }
    }
}
