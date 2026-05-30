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
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['success' => false, 'message' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $bookingType = $this->getValue($data, ['bookingType', 'booking_type']);
        $isHabal = is_string($bookingType) && strtolower($bookingType) === 'habal-habal';

        $customer = null;
        if ($user->getId() !== null) {
            $customer = $entityManager->getRepository(User::class)->find($user->getId());
        }

        if (!$customer && $user->getEmail()) {
            $customer = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        }

        if (!$customer && !$isHabal) {
            error_log('ApiBookingController create exception: authenticated user not found in DB, id=' . ($user->getId() ?? 'null') . ', email=' . ($user->getEmail() ?? 'null'));
            return $this->json(['success' => false, 'message' => 'Authenticated user not found'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$customer) {
            error_log('ApiBookingController create notice: creating booking without persisted customer for habal-habal booking');
        }
        if (!is_array($data)) {
            return $this->json(['success' => false, 'message' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $bookingType = $this->getValue($data, ['bookingType', 'booking_type']);
        $pickupAddress = $this->getValue($data, ['pickupAddress', 'pickup_address']);
        $deliveryAddress = $this->getValue($data, ['deliveryAddress', 'delivery_address']);
        $customerName = $this->getValue($data, ['customerName', 'customer_name']) ?: $user->getEmail() ?: $user->getUsername();
        $customerPhone = $this->getValue($data, ['customerPhone', 'customer_phone', 'phoneNumber', 'phone_number']) ?: $user->getEmail();

        if ($isHabal) {
            $customerName = $customerName ?: 'Habal-Habal Rider';
            $customerPhone = $customerPhone ?: 'N/A';
        }

        $required = [
            'bookingType' => $bookingType,
            'pickupAddress' => $pickupAddress,
            'deliveryAddress' => $deliveryAddress,
        ];

        if (!$isHabal) {
            $required['customerName'] = $customerName;
            $required['customerPhone'] = $customerPhone;
        }

        foreach ($required as $field => $value) {
            if (empty($value)) {
                return $this->json(['success' => false, 'message' => "Missing required field: {$field}"], Response::HTTP_BAD_REQUEST);
            }
        }

        $booking = new Booking();
        if ($customer) {
            $booking->setCustomer($customer);
            $booking->setCreatedBy($customer);
        } elseif (!$isHabal) {
            error_log('ApiBookingController: Customer not found and booking is not habal-habal, rejecting');
            return $this->json(['success' => false, 'message' => 'Customer not found'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        $booking->setStatus('pending');
        $booking->setBookingType((string) $bookingType);
        $booking->setPickupAddress((string) $pickupAddress);
        $booking->setDeliveryAddress((string) $deliveryAddress);
        $booking->setCustomerName((string) $customerName);
        $booking->setCustomerPhone((string) $customerPhone);
        $booking->setParcelDescription($this->getValue($data, ['parcelDescription', 'parcel_description']));
        $booking->setParcelType($this->getValue($data, ['parcelType', 'parcel_type']));
        $booking->setParcelWeight($this->getValue($data, ['parcelWeight', 'parcel_weight']) !== null && $this->getValue($data, ['parcelWeight', 'parcel_weight']) !== '' ? (float) $this->getValue($data, ['parcelWeight', 'parcel_weight']) : null);
        $booking->setPriorityLevel($this->getValue($data, ['priorityLevel', 'priority_level']));
        $booking->setSpecialInstructions($this->getValue($data, ['specialInstructions', 'special_instructions']));
        $booking->setEstimatedDistance($this->getValue($data, ['estimatedDistance', 'estimated_distance']) !== null && $this->getValue($data, ['estimatedDistance', 'estimated_distance']) !== '' ? (float) $this->getValue($data, ['estimatedDistance', 'estimated_distance']) : null);
        $booking->setEstimatedDuration($this->getValue($data, ['estimatedDuration', 'estimated_duration']) !== null && $this->getValue($data, ['estimatedDuration', 'estimated_duration']) !== '' ? (float) $this->getValue($data, ['estimatedDuration', 'estimated_duration']) : null);
        $booking->setEstimatedFare($this->getValue($data, ['estimatedFare', 'estimated_fare']) !== null && $this->getValue($data, ['estimatedFare', 'estimated_fare']) !== '' ? (float) $this->getValue($data, ['estimatedFare', 'estimated_fare']) : null);

        try {
            $requestedPickupTime = $this->parseDateTime($this->getValue($data, ['requestedPickupTime', 'requested_pickup_time']));
            $requestedDeliveryTime = $this->parseDateTime($this->getValue($data, ['requestedDeliveryTime', 'requested_delivery_time']));

            $booking->setRequestedPickupTime($requestedPickupTime ?? new \DateTime());
            if ($requestedDeliveryTime) {
                $booking->setRequestedDeliveryTime($requestedDeliveryTime);
            }
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => 'Invalid pickup or delivery time'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $entityManager->persist($booking);
            $entityManager->flush();
        } catch (\Throwable $e) {
            error_log('ApiBookingController create exception: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Unable to create booking'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

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

    private function getValue(array $data, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null) {
                return $data[$key];
            }
        }

        return $default;
    }

    private function parseDateTime(mixed $value): ?\DateTimeInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        return new \DateTime((string) $value);
    }
}
