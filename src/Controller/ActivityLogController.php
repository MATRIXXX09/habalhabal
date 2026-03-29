<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/logs')]
#[IsGranted('ROLE_ADMIN')]
class ActivityLogController extends AbstractController
{
    #[Route('', name: 'app_admin_logs')]
    public function index(Request $request, ActivityLogRepository $logRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $perPage = 25;

        // Get filter parameters
        $filterUser = $request->query->get('user');
        $filterAction = $request->query->get('action');
        $filterDateFrom = $request->query->get('date_from');
        $filterDateTo = $request->query->get('date_to');

        // Build query with filters
        $qb = $logRepository->createQueryBuilder('log')
            ->orderBy('log.dateTime', 'DESC');

        if ($filterUser) {
            $qb->andWhere('log.username LIKE :user')
                ->setParameter('user', '%' . $filterUser . '%');
        }

        if ($filterAction) {
            $qb->andWhere('log.action = :action')
                ->setParameter('action', $filterAction);
        }

        if ($filterDateFrom) {
            $date = \DateTime::createFromFormat('Y-m-d', $filterDateFrom);
            if ($date) {
                $qb->andWhere('log.dateTime >= :dateFrom')
                    ->setParameter('dateFrom', $date);
            }
        }

        if ($filterDateTo) {
            $date = \DateTime::createFromFormat('Y-m-d', $filterDateTo);
            if ($date) {
                $date->setTime(23, 59, 59);
                $qb->andWhere('log.dateTime <= :dateTo')
                    ->setParameter('dateTo', $date);
            }
        }

        $total = (clone $qb)->select('COUNT(log.id)')->getQuery()->getSingleScalarResult();
        $logs = $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        $totalPages = ceil($total / $perPage);

        // Get unique actions for filter dropdown
        $actions = $logRepository->createQueryBuilder('log')
            ->select('DISTINCT log.action')
            ->orderBy('log.action', 'ASC')
            ->getQuery()
            ->getScalarResult();

        $actionList = array_column($actions, 'action');

        return $this->render('admin/logs/index.html.twig', [
            'logs' => $logs,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'actions' => $actionList,
            'filterUser' => $filterUser,
            'filterAction' => $filterAction,
            'filterDateFrom' => $filterDateFrom,
            'filterDateTo' => $filterDateTo,
        ]);
    }
}
