<?php

namespace App\Controller\Staff;

use App\Entity\Complaint;
use App\Form\ComplaintType;
use App\Repository\ComplaintRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff/complaints', name: 'staff_complaint_')]
#[IsGranted('ROLE_STAFF')]
class StaffComplaintController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ComplaintRepository $repository): Response
    {
        $user = $this->getUser();
        $complaints = $repository->findBy(['createdBy' => $user]);

        return $this->render('staff/complaint/index.html.twig', [
            'complaints' => $complaints,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $complaint = new Complaint();
        $form = $this->createForm(ComplaintType::class, $complaint);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $complaint->setCreatedBy($this->getUser());
            $complaint->setCreatedAt(new \DateTime());
            $em->persist($complaint);
            $em->flush();

            $this->addFlash('success', 'Complaint created successfully!');
            return $this->redirectToRoute('staff_complaint_index');
        }

        return $this->render('staff/complaint/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Complaint $complaint): Response
    {
        $this->checkOwnership($complaint);

        return $this->render('staff/complaint/show.html.twig', [
            'complaint' => $complaint,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Complaint $complaint, EntityManagerInterface $em): Response
    {
        $this->checkOwnership($complaint);

        $form = $this->createForm(ComplaintType::class, $complaint);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Complaint updated successfully!');
            return $this->redirectToRoute('staff_complaint_index');
        }

        return $this->render('staff/complaint/edit.html.twig', [
            'form' => $form,
            'complaint' => $complaint,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Complaint $complaint, EntityManagerInterface $em): Response
    {
        $this->checkOwnership($complaint);

        if ($this->isCsrfTokenValid('delete' . $complaint->getId(), $request->request->get('_token'))) {
            $em->remove($complaint);
            $em->flush();
            $this->addFlash('success', 'Complaint deleted successfully!');
        }

        return $this->redirectToRoute('staff_complaint_index');
    }

    private function checkOwnership(Complaint $complaint): void
    {
        if ($complaint->getCreatedBy()?->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('You can only manage your own complaints.');
        }
    }
}
