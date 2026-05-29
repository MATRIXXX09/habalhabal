<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\RealtimeBroadcaster;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ApiWalletController extends AbstractController
{
    #[Route('/api/wallet/top-up', name: 'api_wallet_top_up', methods: ['POST'])]
    #[Route('/api/wallet/topup', name: 'api_wallet_top_up_alias', methods: ['POST'])]
    #[Route('/api/top-up', name: 'api_top_up_alias', methods: ['POST'])]
    #[Route('/api/topup', name: 'api_topup_alias', methods: ['POST'])]
    #[Route('/api/add-cash', name: 'api_add_cash_alias', methods: ['POST'])]
    public function topUp(
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

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            return $this->json(['success' => false, 'message' => 'Amount must be greater than zero'], Response::HTTP_BAD_REQUEST);
        }

        $user->addWalletBalance($amount);
        $entityManager->flush();

        $realtimeBroadcaster->broadcastDatabaseChanged([
            'source' => 'api-wallet-top-up',
            'userId' => $user->getId(),
            'amount' => $amount,
        ]);

        return $this->json([
            'success' => true,
            'message' => 'Top up successful',
            'balance' => $user->getWalletBalance(),
        ]);
    }

    #[Route('/api/wallet/balance', name: 'api_wallet_balance', methods: ['GET'])]
    public function balance(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'balance' => $user->getWalletBalance(),
        ]);
    }
}
