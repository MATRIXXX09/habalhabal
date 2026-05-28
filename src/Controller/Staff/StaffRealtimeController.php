<?php

namespace App\Controller\Staff;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff/realtime', name: 'staff_realtime_')]
#[IsGranted('ROLE_STAFF')]
class StaffRealtimeController extends AbstractController
{
    #[Route('/websocket', name: 'websocket_demo', methods: ['GET'])]
    public function websocketDemo(): Response
    {
        return $this->render('staff/realtime/websocket_demo.html.twig', [
            'wsUrl' => 'ws://127.0.0.1:8081',
        ]);
    }
}
