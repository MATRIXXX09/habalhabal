<?php

namespace App\Controller\Staff;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff/realtime', name: 'staff_realtime_')]
#[IsGranted('ROLE_STAFF')]
class StaffRealtimeController extends AbstractController
{
    #[Route('/websocket', name: 'websocket_demo', methods: ['GET'])]
    public function websocketDemo(Request $request): Response
    {
        $scheme = $request->isSecure() ? 'wss' : 'ws';

        return $this->render('staff/realtime/websocket_demo.html.twig', [
            'wsUrl' => sprintf('%s://%s/ws', $scheme, $request->getHttpHost()),
        ]);
    }
}
