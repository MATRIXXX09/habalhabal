<?php

namespace App\Controller\Staff;

use App\Entity\Booking;
use App\Entity\User;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Repository\RiderRepository;
use App\Service\DriverAvailabilityService;
use App\Service\RealtimeBroadcaster;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/staff/bookings', name: 'staff_booking_')]
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
        $this->assertBookingAccess();
        $bookings = $this->bookingRepository->findAllOrdered();

        return $this->render('staff/booking/index.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, RealtimeBroadcaster $realtimeBroadcaster): Response
    {
        $this->assertBookingAccess();
        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking, [
            'edit_mode' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                // Log form errors for debugging
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                error_log('StaffBookingController form validation failed: ' . implode(', ', $errors));
                
                // Flash message for user
                $this->addFlash('error', 'Form validation failed. Please check all required fields.');
                
                return $this->render('staff/booking/new.html.twig', [
                    'form' => $form,
                ]);
            }

            try {
                // Ensure required fields are set
                $currentUser = $this->getUser();
                if (!$currentUser) {
                    throw new \Exception('No authenticated user found');
                }

                // Staff-created bookings should track the creator but not assign the staff user as the customer.
                $booking->setCreatedBy($currentUser);
                $booking->setStatus('pending');

                // Verify all required fields
                if (!$booking->getBookingType()) {
                    throw new \Exception('Booking type is required');
                }
                if (!$booking->getPickupAddress()) {
                    throw new \Exception('Pickup address is required');
                }
                if (!$booking->getDeliveryAddress()) {
                    throw new \Exception('Delivery address is required');
                }
                if (!$booking->getCustomerName()) {
                    throw new \Exception('Customer name is required');
                }
                if (!$booking->getCustomerPhone()) {
                    throw new \Exception('Customer phone is required');
                }
                if (!$booking->getRequestedPickupTime()) {
                    throw new \Exception('Requested pickup time is required');
                }

                $em->persist($booking);
                $em->flush();

                error_log('StaffBookingController new: Booking created successfully, ID=' . $booking->getId());

                $realtimeBroadcaster->broadcastDatabaseChanged([
                    'source' => 'staff-booking-new',
                    'bookingId' => $booking->getId(),
                ]);

                $this->addFlash('success', 'Booking created successfully!');
                return $this->redirectToRoute('staff_booking_index');
            } catch (\Exception $e) {
                error_log('StaffBookingController new exception: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
                $this->addFlash('error', 'Error creating booking: ' . $e->getMessage());
                
                return $this->render('staff/booking/new.html.twig', [
                    'form' => $form,
                ]);
            }
        }

        return $this->render('staff/booking/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Booking $booking): Response
    {
        $this->assertBookingAccess();
        $this->checkOwnership($booking);

        // Get available drivers if booking is still pending
        $availableDrivers = [];
        if ($booking->getStatus() === 'pending' && $booking->getRequestedPickupTime() instanceof \DateTimeInterface) {
            $pickupTime = $booking->getRequestedPickupTime();
            $deliveryTime = $booking->getRequestedDeliveryTime();

            if (!$deliveryTime) {
                // Clone or modify pickup time (handle DateTimeImmutable and DateTime)
                if ($pickupTime instanceof \DateTimeImmutable) {
                    $deliveryTime = $pickupTime->modify('+2 hours');
                } else {
                    /** @var \DateTime $tmpPickup */
                    $tmpPickup = $pickupTime;
                    $deliveryTime = (clone $tmpPickup);
                    $deliveryTime->modify('+2 hours');
                }
            }

            try {
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
            } catch (\Throwable $e) {
                // Don't let driver-availability failures break the page; show an empty list instead
                $availableDrivers = [];
            }
        }

        return $this->render('staff/booking/show.html.twig', [
            'booking' => $booking,
            'availableDrivers' => $availableDrivers,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Booking $booking, EntityManagerInterface $em, RealtimeBroadcaster $realtimeBroadcaster): Response
    {
        $this->assertBookingAccess();
        $this->checkOwnership($booking);

        $form = $this->createForm(BookingType::class, $booking, [
            'edit_mode' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $booking->setUpdatedAt(new \DateTime());
            $em->flush();
            $realtimeBroadcaster->broadcastDatabaseChanged([
                'source' => 'staff-booking-edit',
                'bookingId' => $booking->getId(),
            ]);

            $this->addFlash('success', 'Booking updated successfully!');
            return $this->redirectToRoute('staff_booking_show', ['id' => $booking->getId()]);
        }

        return $this->render('staff/booking/edit.html.twig', [
            'form' => $form,
            'booking' => $booking,
            'back_route' => 'staff_booking_show',
            'cancel_route' => 'staff_booking_show',
        ]);
    }

    #[Route('/{id}/status', name: 'status_update', methods: ['POST'])]
    public function updateStatus(Request $request, Booking $booking, EntityManagerInterface $em, RealtimeBroadcaster $realtimeBroadcaster): Response
    {
        $this->assertBookingAccess();
        $this->checkOwnership($booking);

        if (!$this->isCsrfTokenValid('booking_status_' . $booking->getId(), $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid booking status token.'], Response::HTTP_BAD_REQUEST);
            }

            $this->addFlash('error', 'Invalid booking status token.');
            return $this->redirectToRoute('staff_booking_index');
        }

        $status = (string) $request->request->get('status', '');
        $allowedStatuses = ['pending', 'confirmed', 'assigned', 'in_transit', 'completed', 'cancelled'];

        if (!in_array($status, $allowedStatuses, true)) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid booking status selected.'], Response::HTTP_BAD_REQUEST);
            }

            $this->addFlash('error', 'Invalid booking status selected.');
            return $this->redirectToRoute('staff_booking_index');
        }

        $booking->setStatus($status);
        $booking->setUpdatedAt(new \DateTime());
        $em->flush();
        $realtimeBroadcaster->broadcastDatabaseChanged([
            'source' => 'staff-booking-status',
            'bookingId' => $booking->getId(),
            'status' => $status,
        ]);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'bookingId' => $booking->getId(),
                'status' => $status,
                'statusLabel' => ucfirst(str_replace('_', ' ', $status)),
            ]);
        }

        $this->addFlash('success', 'Booking status updated successfully!');

        return $this->redirectToRoute('staff_booking_index');
    }

    #[Route('/{id}/assign-driver/{driverId}', name: 'assign_driver', methods: ['POST'])]
    public function assignDriver(Request $request, Booking $booking, int $driverId, EntityManagerInterface $em, RealtimeBroadcaster $realtimeBroadcaster): Response
    {
        $this->assertBookingAccess();
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
        $realtimeBroadcaster->broadcastDatabaseChanged([
            'source' => 'staff-booking-assign-driver',
            'bookingId' => $booking->getId(),
            'driverId' => $driverId,
        ]);

        $this->addFlash('success', sprintf('Booking assigned to driver %s successfully!', $driver->getFirstName()));

        return $this->redirectToRoute('staff_booking_show', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(Request $request, Booking $booking, EntityManagerInterface $em, RealtimeBroadcaster $realtimeBroadcaster): Response
    {
        $this->assertBookingAccess();
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
        $realtimeBroadcaster->broadcastDatabaseChanged([
            'source' => 'staff-booking-cancel',
            'bookingId' => $booking->getId(),
        ]);

        $this->addFlash('success', 'Booking cancelled successfully!');

        return $this->redirectToRoute('staff_booking_index');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Booking $booking, EntityManagerInterface $em, RealtimeBroadcaster $realtimeBroadcaster): Response
    {
        $this->assertBookingAccess();
        $this->checkOwnership($booking);

        if (!$this->isCsrfTokenValid('delete_' . $booking->getId(), $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse(['error' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }

        if ($booking->getStatus() !== 'pending') {
            $this->addFlash('error', 'Can only delete pending bookings.');
            return $this->redirectToRoute('staff_booking_show', ['id' => $booking->getId()]);
        }

        $em->remove($booking);
        $em->flush();
        $realtimeBroadcaster->broadcastDatabaseChanged([
            'source' => 'staff-booking-delete',
            'bookingId' => $booking->getId(),
        ]);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'bookingId' => $booking->getId(),
            ]);
        }

        $this->addFlash('success', 'Booking deleted successfully!');
        return $this->redirectToRoute('staff_booking_index');
    }

    /**
     * AJAX endpoint to check driver availability and get estimates
     */
    #[Route('/api/check-availability', name: 'check_availability', methods: ['POST'])]
    public function checkAvailability(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->assertBookingAccess();
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
        // Allow admins and staff to manage bookings
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            return;
        }

        $currentUser = $this->getUser();
        $createdBy = $booking->getCreatedBy();

        if (!$createdBy instanceof User || !$currentUser instanceof User || $createdBy->getId() !== $currentUser->getId()) {
            throw $this->createAccessDeniedException('You can only manage your own bookings.');
        }
    }

    private function assertBookingAccess(): void
    {
        if ($this->isGranted('ROLE_STAFF') || $this->isGranted('ROLE_ADMIN')) {
            return;
        }

        throw $this->createAccessDeniedException('Access denied.');
    }
}
