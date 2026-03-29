<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'booking')]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $customer = null;

    #[ORM\Column(length: 50)]
    private ?string $bookingType = null; // 'delivery' or 'pickup'

    #[ORM\Column(length: 255)]
    private ?string $pickupAddress = null;

    #[ORM\Column(length: 255)]
    private ?string $deliveryAddress = null;

    #[ORM\Column(length: 255)]
    private ?string $customerName = null;

    #[ORM\Column(length: 20)]
    private ?string $customerPhone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $parcelDescription = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $parcelType = null; // 'document', 'small_package', 'medium_package', 'large_package'

    #[ORM\Column(nullable: true)]
    private ?float $parcelWeight = null; // in kg

    #[ORM\Column(length: 50)]
    private ?string $status = 'pending'; // pending, confirmed, assigned, in_transit, completed, cancelled

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $requestedPickupTime = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $requestedDeliveryTime = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Rider::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Rider $assignedDriver = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $assignedAt = null;

    #[ORM\Column(nullable: true)]
    private ?float $estimatedDistance = null; // in km

    #[ORM\Column(nullable: true)]
    private ?float $estimatedDuration = null; // in minutes

    #[ORM\Column(nullable: true)]
    private ?float $estimatedFare = null; // in currency

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $priorityLevel = null; // 'standard', 'urgent', 'scheduled'

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $specialInstructions = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $cancellationReason = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'pending';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;
        return $this;
    }

    public function getBookingType(): ?string
    {
        return $this->bookingType;
    }

    public function setBookingType(string $bookingType): static
    {
        $this->bookingType = $bookingType;
        return $this;
    }

    public function getPickupAddress(): ?string
    {
        return $this->pickupAddress;
    }

    public function setPickupAddress(string $pickupAddress): static
    {
        $this->pickupAddress = $pickupAddress;
        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(string $deliveryAddress): static
    {
        $this->deliveryAddress = $deliveryAddress;
        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): static
    {
        $this->customerName = $customerName;
        return $this;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(string $customerPhone): static
    {
        $this->customerPhone = $customerPhone;
        return $this;
    }

    public function getParcelDescription(): ?string
    {
        return $this->parcelDescription;
    }

    public function setParcelDescription(?string $parcelDescription): static
    {
        $this->parcelDescription = $parcelDescription;
        return $this;
    }

    public function getParcelType(): ?string
    {
        return $this->parcelType;
    }

    public function setParcelType(?string $parcelType): static
    {
        $this->parcelType = $parcelType;
        return $this;
    }

    public function getParcelWeight(): ?float
    {
        return $this->parcelWeight;
    }

    public function setParcelWeight(?float $parcelWeight): static
    {
        $this->parcelWeight = $parcelWeight;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getRequestedPickupTime(): ?\DateTimeInterface
    {
        return $this->requestedPickupTime;
    }

    public function setRequestedPickupTime(?\DateTimeInterface $requestedPickupTime): static
    {
        $this->requestedPickupTime = $requestedPickupTime;
        return $this;
    }

    public function getRequestedDeliveryTime(): ?\DateTimeInterface
    {
        return $this->requestedDeliveryTime;
    }

    public function setRequestedDeliveryTime(?\DateTimeInterface $requestedDeliveryTime): static
    {
        $this->requestedDeliveryTime = $requestedDeliveryTime;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getAssignedDriver(): ?Rider
    {
        return $this->assignedDriver;
    }

    public function setAssignedDriver(?Rider $assignedDriver): static
    {
        $this->assignedDriver = $assignedDriver;
        return $this;
    }

    public function getAssignedAt(): ?\DateTimeInterface
    {
        return $this->assignedAt;
    }

    public function setAssignedAt(?\DateTimeInterface $assignedAt): static
    {
        $this->assignedAt = $assignedAt;
        return $this;
    }

    public function getEstimatedDistance(): ?float
    {
        return $this->estimatedDistance;
    }

    public function setEstimatedDistance(?float $estimatedDistance): static
    {
        $this->estimatedDistance = $estimatedDistance;
        return $this;
    }

    public function getEstimatedDuration(): ?float
    {
        return $this->estimatedDuration;
    }

    public function setEstimatedDuration(?float $estimatedDuration): static
    {
        $this->estimatedDuration = $estimatedDuration;
        return $this;
    }

    public function getEstimatedFare(): ?float
    {
        return $this->estimatedFare;
    }

    public function setEstimatedFare(?float $estimatedFare): static
    {
        $this->estimatedFare = $estimatedFare;
        return $this;
    }

    public function getPriorityLevel(): ?string
    {
        return $this->priorityLevel;
    }

    public function setPriorityLevel(?string $priorityLevel): static
    {
        $this->priorityLevel = $priorityLevel;
        return $this;
    }

    public function getSpecialInstructions(): ?string
    {
        return $this->specialInstructions;
    }

    public function setSpecialInstructions(?string $specialInstructions): static
    {
        $this->specialInstructions = $specialInstructions;
        return $this;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function setCancellationReason(?string $cancellationReason): static
    {
        $this->cancellationReason = $cancellationReason;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }
}
