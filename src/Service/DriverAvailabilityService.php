<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Rider;
use App\Repository\BookingRepository;
use App\Repository\RiderRepository;
use DateTime;
use DateTimeInterface;

class DriverAvailabilityService
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private RiderRepository $riderRepository,
    ) {}

    /**
     * Check available drivers for a specific time window
     * Similar to Grab, Move It, LaLaMove availability checks
     */
    public function getAvailableDrivers(
        DateTimeInterface $pickupTime,
        DateTimeInterface $deliveryTime,
        ?string $vehicleType = null
    ): array {
        // Get all available riders
        $criteria = [
            'isAvailable' => true,
            'status' => 'available',
        ];

        if ($vehicleType) {
            $criteria['vehicleType'] = $vehicleType;
        }

        $allRiders = $this->riderRepository->findBy($criteria);

        // Filter riders who don't have conflicting bookings
        $availableDrivers = [];

        foreach ($allRiders as $rider) {
            if ($this->isDriverAvailableForTimeSlot($rider, $pickupTime, $deliveryTime)) {
                $availableDrivers[] = $rider;
            }
        }

        return $availableDrivers;
    }

    /**
     * Check if a specific driver is available for the time slot
     */
    public function isDriverAvailableForTimeSlot(
        Rider $driver,
        DateTimeInterface $pickupTime,
        DateTimeInterface $deliveryTime
    ): bool {
        // Check driver status
        if (!$driver->isAvailable() || $driver->getStatus() !== 'available') {
            return false;
        }

        // Get all active bookings for this driver
        $activeBookings = $this->bookingRepository->createQueryBuilder('b')
            ->where('b.assignedDriver = :driver')
            ->andWhere('b.status NOT IN (:completedStatuses)')
            ->setParameter('driver', $driver)
            ->setParameter('completedStatuses', ['completed', 'cancelled'])
            ->getQuery()
            ->getResult();

        // Check for time conflicts
        foreach ($activeBookings as $booking) {
            if ($this->hasTimeConflict($booking, $pickupTime, $deliveryTime)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if there's a time conflict between two bookings
     */
    private function hasTimeConflict(Booking $booking, DateTimeInterface $pickupTime, DateTimeInterface $deliveryTime): bool
    {
        $bookingStart = $booking->getRequestedPickupTime();
        $bookingEnd = $booking->getRequestedDeliveryTime();
        
        if (!$bookingEnd) {
            // Default to 1 hour if not specified
            $bookingEnd = new DateTime($bookingStart->format('Y-m-d H:i:s'));
            $bookingEnd->modify('+1 hour');
        }

        // Add buffer time (30 minutes)
        $bufferSeconds = 30 * 60;
        $bookingStartWithBuffer = new DateTime($bookingStart->format('Y-m-d H:i:s'));
        $bookingEndWithBuffer = new DateTime($bookingEnd->format('Y-m-d H:i:s'));
        
        $bookingStartWithBuffer->modify('-' . $bufferSeconds . ' seconds');
        $bookingEndWithBuffer->modify('+' . $bufferSeconds . ' seconds');

        // Check for overlap
        return !($deliveryTime < $bookingStartWithBuffer || $pickupTime > $bookingEndWithBuffer);
    }

    /**
     * Get estimated fare based on distance and parcel type
     * Simple calculation based on base fare + distance
     */
    public function calculateEstimatedFare(float $distance, string $parcelType): float
    {
        // Base fare in currency units
        $baseFare = 50.0;
        
        // Distance rate per km
        $distanceRate = 10.0;
        
        // Parcel type multiplier
        $parcelMultipliers = [
            'document' => 1.0,
            'small_package' => 1.2,
            'medium_package' => 1.5,
            'large_package' => 2.0,
        ];

        $multiplier = $parcelMultipliers[$parcelType] ?? 1.0;
        $fare = ($baseFare + ($distance * $distanceRate)) * $multiplier;

        return round($fare, 2);
    }

    /**
     * Get estimated duration based on distance
     * Simple calculation (average speed assumed as 30 km/h in city)
     */
    public function calculateEstimatedDuration(float $distance): float
    {
        // Average speed in city: 30 km/h
        $averageSpeed = 30.0;
        
        // Add 10 minutes for pickup and delivery combined
        $additionalTime = 10.0;
        
        // Duration in minutes
        $duration = ($distance / $averageSpeed) * 60 + $additionalTime;

        return round($duration, 0);
    }

    /**
     * Get driver stats for display
     */
    public function getDriverStats(Rider $driver): array
    {
        $totalBookings = $this->bookingRepository->count(['assignedDriver' => $driver]);
        
        $completedBookings = $this->bookingRepository->count([
            'assignedDriver' => $driver,
            'status' => 'completed',
        ]);

        $rating = $totalBookings > 0 ? ($completedBookings / $totalBookings) * 5 : 0;

        return [
            'totalBookings' => $totalBookings,
            'completedBookings' => $completedBookings,
            'rating' => round($rating, 1),
            'vehicle' => $driver->getPlateNumber(),
            'vehicleType' => $driver->getVehicleType(),
        ];
    }
}
