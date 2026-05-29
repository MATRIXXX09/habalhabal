<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\User;
use App\Service\RealtimeBroadcaster;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ApiBookingController extends AbstractController
{
    #[Route('/api/bookings', name: 'api_bookings_create', methods: ['POST'])]
    #[Route('/api/booking', name: 'api_booking_create_alias', methods: ['POST'])]
    #[Route('/api/bookings/create', name: 'api_bookings_create_alias', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        RealtimeBroadcaster $realtimeBroadcaster,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['success' => false, 'message' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $required = ['bookingType', 'pickupAddress', 'deliveryAddress', 'customerName', 'customerPhone'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->json(['success' => false, 'message' => "Missing required field: {$field}"], Response::HTTP_BAD_REQUEST);
            }
        }

        $booking = new Booking();
        $booking->setCustomer($user);
        $booking->setCreatedBy($user);
        $booking->setStatus('pending');
        $booking->setBookingType((string) $data['bookingType']);
        $booking->setPickupAddress((string) $data['pickupAddress']);
        $booking->setDeliveryAddress((string) $data['deliveryAddress']);
        $booking->setCustomerName((string) $data['customerName']);
        $booking->setCustomerPhone((string) $data['customerPhone']);
        $booking->setParcelDescription(isset($data['parcelDescription']) ? (string) $data['parcelDescription'] : null);
        $booking->setParcelType(isset($data['parcelType']) ? (string) $data['parcelType'] : null);
        $booking->setParcelWeight(isset($data['parcelWeight']) && $data['parcelWeight'] !== '' ? (float) $data['parcelWeight'] : null);
        $booking->setPriorityLevel(isset($data['priorityLevel']) ? (string) $data['priorityLevel'] : null);
        $booking->setSpecialInstructions(isset($data['specialInstructions']) ? (string) $data['specialInstructions'] : null);
        $booking->setEstimatedDistance(isset($data['estimatedDistance']) && $data['estimatedDistance'] !== '' ? (float) $data['estimatedDistance'] : null);
        $booking->setEstimatedDuration(isset($data['estimatedDuration']) && $data['estimatedDuration'] !== '' ? (float) $data['estimatedDuration'] : null);
        $booking->setEstimatedFare(isset($data['estimatedFare']) && $data['estimatedFare'] !== '' ? (float) $data['estimatedFare'] : null);

        try {
            $booking->setRequestedPickupTime(new \DateTime((string) $data['requestedPickupTime']));
            if (!empty($data['requestedDeliveryTime'])) {
                $booking->setRequestedDeliveryTime(new \DateTime((string) $data['requestedDeliveryTime']));
            }
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => 'Invalid pickup or delivery time'], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($booking);
        $entityManager->flush();

        $realtimeBroadcaster->broadcastDatabaseChanged([
            'source' => 'api-booking-create',
            'bookingId' => $booking->getId(),
        ]);

        return $this->json([
            'success' => true,
            'message' => 'Booking created successfully',
            'booking' => [
                'id' => $booking->getId(),
                'status' => $booking->getStatus(),
                'bookingType' => $booking->getBookingType(),
                'customerName' => $booking->getCustomerName(),
                'customerPhone' => $booking->getCustomerPhone(),
                'pickupAddress' => $booking->getPickupAddress(),
                'deliveryAddress' => $booking->getDeliveryAddress(),
                'createdAt' => $booking->getCreatedAt()?->format('Y-m-d H:i:s'),
            ],
        ], Response::HTTP_CREATED);
    }
}
