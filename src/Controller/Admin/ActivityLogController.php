<?php

namespace App\Controller\Admin;

use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/activity-logs')]
#[IsGranted('ROLE_ADMIN')]
class ActivityLogController extends AbstractController
{
    #[Route('', name: 'app_admin_activity_logs')]
    public function index(
        Request $request,
        ActivityLogRepository $activityLogRepository,
        UserRepository $userRepository
    ): Response {
        // Get filter parameters
        $username = $request->query->get('username', '');
        $action = $request->query->get('action', '');
        $dateFrom = $request->query->get('dateFrom', '');
        $dateTo = $request->query->get('dateTo', '');
        $page = $request->query->getInt('page', 1);

        // Build query
        $qb = $activityLogRepository->createQueryBuilder('a')
            ->orderBy('a.dateTime', 'DESC');

        // Apply filters
        if (!empty($username)) {
            $qb->andWhere('a.username LIKE :username')
                ->setParameter('username', '%' . $username . '%');
        }

        if (!empty($action)) {
            $qb->andWhere('a.action = :action')
                ->setParameter('action', $action);
        }

        if (!empty($dateFrom)) {
            $dateFromObj = \DateTime::createFromFormat('Y-m-d', $dateFrom);
            if ($dateFromObj) {
                $qb->andWhere('a.dateTime >= :dateFrom')
                    ->setParameter('dateFrom', $dateFromObj);
            }
        }

        if (!empty($dateTo)) {
            $dateToObj = \DateTime::createFromFormat('Y-m-d', $dateTo);
            if ($dateToObj) {
                // Set time to end of day
                $dateToObj->setTime(23, 59, 59);
                $qb->andWhere('a.dateTime <= :dateTo')
                    ->setParameter('dateTo', $dateToObj);
            }
        }

        // Get total count before pagination
        $totalCount = count($qb->getQuery()->getResult());

        // Pagination
        $perPage = 50;
        $totalPages = ceil($totalCount / $perPage);
        
        if ($page < 1) {
            $page = 1;
        } elseif ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }

        $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $logs = $qb->getQuery()->getResult();

        // Get unique actions for filter dropdown
        $allLogsQb = $activityLogRepository->createQueryBuilder('a')
            ->select('DISTINCT a.action')
            ->orderBy('a.action', 'ASC');
        $actions = array_map(fn($row) => $row['action'], $allLogsQb->getQuery()->getResult());

        return $this->render('admin/activity_logs/index.html.twig', [
            'logs' => $logs,
            'username' => $username,
            'action' => $action,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'actions' => $actions,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_activity_log_detail')]
    public function detail(int $id, ActivityLogRepository $activityLogRepository): Response
    {
        $log = $activityLogRepository->find($id);

        if (!$log) {
            throw $this->createNotFoundException('Activity log not found');
        }

        return $this->render('admin/activity_logs/detail.html.twig', [
            'log' => $log,
        ]);
    }
}
