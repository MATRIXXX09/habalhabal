<?php

namespace App\Controller;

use App\Entity\Complaint;
use App\Repository\ComplaintRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/complaints')]
class AdminComplaintController extends AbstractController
{
    #[Route('', name: 'app_admin_complaints')]
    public function index(ComplaintRepository $complaintRepository): Response
    {
        return $this->render('admin/complaints/index.html.twig', [
            'complaints' => $complaintRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_complaints_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $complaint = new Complaint();
            $complaint->setCustomerName($request->request->get('customerName'));
            $complaint->setMessage($request->request->get('message'));
            $complaint->setStatus('pending');
            $em->persist($complaint);
            $em->flush();
            return $this->redirectToRoute('app_admin_complaints');
        }
        return $this->render('admin/complaints/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'app_admin_complaints_edit')]
    public function edit(Request $request, Complaint $complaint, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $complaint->setCustomerName($request->request->get('customerName'));
            $complaint->setMessage($request->request->get('message'));
            $complaint->setStatus($request->request->get('status'));
            $em->flush();
            return $this->redirectToRoute('app_admin_complaints');
        }
        return $this->render('admin/complaints/edit.html.twig', [
            'complaint' => $complaint
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_complaints_delete', methods: ['POST'])]
    public function delete(Complaint $complaint, EntityManagerInterface $em): Response
    {
        $em->remove($complaint);
        $em->flush();
        return $this->redirectToRoute('app_admin_complaints');
    }
}
