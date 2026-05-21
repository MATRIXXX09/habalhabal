<?php

namespace App\Controller;

use App\Entity\Shipment;
use App\Repository\ShipmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShipmentApiController extends AbstractController
{
    #[Route('/api/shipments', name: 'api_shipments', methods: ['GET'])]
    #[Route('/api/shipment', name: 'api_shipment', methods: ['GET'])]
    #[Route('/api/list-shipment', name: 'api_list_shipment', methods: ['GET'])]
    public function getShipments(ShipmentRepository $repository): JsonResponse
    {
        $shipments = $repository->findAll();

        $data = [];
        foreach ($shipments as $shipment) {
            $data[] = [
                'id' => $shipment->getId(),
                'trackingNumber' => $shipment->getTrackingNumber(),
                'origin' => $shipment->getOrigin(),
                'destination' => $shipment->getDestination(),
                'status' => $shipment->getStatus(),
                'senderName' => $shipment->getSenderName(),
                'createdAt' => $shipment->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json([
            'shipments' => $data,
            'total' => count($data),
        ]);
    }

    #[Route('/api/shipments', name: 'api_shipments_create', methods: ['POST'])]
    public function createShipment(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validate required fields
            $required = ['trackingNumber', 'origin', 'destination', 'status', 'senderName'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->json(
                        ['error' => "Missing required field: $field"],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }

            // Create shipment
            $shipment = new Shipment();
            $shipment->setTrackingNumber($data['trackingNumber']);
            $shipment->setOrigin($data['origin']);
            $shipment->setDestination($data['destination']);
            $shipment->setStatus($data['status']);
            $shipment->setSenderName($data['senderName']);
            $shipment->setCreatedAt(new \DateTime());

            $entityManager->persist($shipment);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Shipment created successfully',
                'shipment' => [
                    'id' => $shipment->getId(),
                    'trackingNumber' => $shipment->getTrackingNumber(),
                    'origin' => $shipment->getOrigin(),
                    'destination' => $shipment->getDestination(),
                    'status' => $shipment->getStatus(),
                    'senderName' => $shipment->getSenderName(),
                    'createdAt' => $shipment->getCreatedAt()->format('Y-m-d H:i:s'),
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
