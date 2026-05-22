<?php

namespace App\Controller\Staff;

use App\Entity\Shipment;
use App\Entity\User;
use App\Form\ShipmentType;
use App\Repository\ShipmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff/shipments', name: 'staff_shipment_')]
#[IsGranted('ROLE_STAFF')]
class StaffShipmentController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ShipmentRepository $repository): Response
    {
        $user = $this->getUser();
        $shipments = $repository->findBy(['createdBy' => $user]);

        return $this->render('staff/shipment/index.html.twig', [
            'shipments' => $shipments,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $shipment = new Shipment();
        $shipment->setStatus('pending');
        $form = $this->createForm(ShipmentType::class, $shipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $shipment->setCreatedBy($this->getUser());
            $shipment->setCreatedAt(new \DateTime());
            $em->persist($shipment);
            $em->flush();

            $this->addFlash('success', 'Shipment created successfully!');
            return $this->redirectToRoute('staff_shipment_index');
        }

        return $this->render('staff/shipment/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Shipment $shipment): Response
    {
        $this->checkOwnership($shipment);

        return $this->render('staff/shipment/show.html.twig', [
            'shipment' => $shipment,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Shipment $shipment, EntityManagerInterface $em): Response
    {
        $this->checkOwnership($shipment);

        $form = $this->createForm(ShipmentType::class, $shipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Shipment updated successfully!');
            return $this->redirectToRoute('staff_shipment_index');
        }

        return $this->render('staff/shipment/edit.html.twig', [
            'form' => $form,
            'shipment' => $shipment,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Shipment $shipment, EntityManagerInterface $em): Response
    {
        $this->checkOwnership($shipment);

        if ($this->isCsrfTokenValid('delete' . $shipment->getId(), $request->request->get('_token'))) {
            $em->remove($shipment);
            $em->flush();
            $this->addFlash('success', 'Shipment deleted successfully!');
        }

        return $this->redirectToRoute('staff_shipment_index');
    }

    private function checkOwnership(Shipment $shipment): void
    {
        $currentUser = $this->getUser();
        $createdBy = $shipment->getCreatedBy();
        
        if (!$createdBy instanceof User || !$currentUser instanceof User || $createdBy->getId() !== $currentUser->getId()) {
            throw $this->createAccessDeniedException('You can only manage your own shipments.');
        }
    }
}
