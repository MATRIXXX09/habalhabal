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
    #[Route('/wallet/top-up', name: 'api_wallet_top_up_web_alias', methods: ['POST'])]
    #[Route('/wallet/topup', name: 'api_wallet_top_up_web_alias_2', methods: ['POST'])]
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
            'balance' => $this->getWalletBalance($user, $entityManager),
        ]);
    }

    #[Route('/api/wallet/balance', name: 'api_wallet_balance', methods: ['GET'])]
    #[Route('/api/wallet', name: 'api_wallet_balance_alias', methods: ['GET'])]
    #[Route('/wallet', name: 'api_wallet_balance_web_alias', methods: ['GET'])]
    public function balance(
        EntityManagerInterface $entityManager,
        #[CurrentUser] ?User $user,
    ): JsonResponse
    {
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'balance' => $this->getWalletBalance($user, $entityManager),
        ]);
    }

    private function getWalletBalance(User $user, EntityManagerInterface $entityManager): float
    {
        $balance = (float) $user->getWalletBalance();

        try {
            $connection = $entityManager->getConnection();
            $topUpValue = $connection->fetchOne('SELECT COALESCE(top_up, 0.00) FROM `user` WHERE id = ?', [$user->getId()]);
            if ($topUpValue !== false) {
                $balance += (float) $topUpValue;
            }
        } catch (\Throwable $exception) {
            // Ignore if the legacy top_up column does not exist or if DB access fails.
        }

        return round($balance, 2);
    }
}
