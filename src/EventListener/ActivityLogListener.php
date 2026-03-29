<?php

namespace App\EventListener;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ActivityLogListener implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $user = $this->security->getUser();

        if (!$user) {
            return;
        }

        $route = $request->attributes->get('_route');
        $method = $request->getMethod();

        // Map routes to loggable actions for both ADMIN and STAFF
        $loggableActions = [
            // ADMIN: User Management
            'app_admin_users_new' => ['action' => 'CREATE', 'type' => 'USER', 'roles' => ['ROLE_ADMIN']],
            'app_admin_users_edit' => ['action' => 'UPDATE', 'type' => 'USER', 'roles' => ['ROLE_ADMIN']],
            'app_admin_users_delete' => ['action' => 'DELETE', 'type' => 'USER', 'roles' => ['ROLE_ADMIN']],
            'app_admin_users_disable' => ['action' => 'UPDATE', 'type' => 'USER', 'roles' => ['ROLE_ADMIN']],
            'app_admin_users_archive' => ['action' => 'UPDATE', 'type' => 'USER', 'roles' => ['ROLE_ADMIN']],
            'app_admin_users_reactivate' => ['action' => 'UPDATE', 'type' => 'USER', 'roles' => ['ROLE_ADMIN']],
            
            // ADMIN: Other Resources
            'app_admin_riders_delete' => ['action' => 'DELETE', 'type' => 'RIDER', 'roles' => ['ROLE_ADMIN']],
            'app_admin_riders_edit' => ['action' => 'UPDATE', 'type' => 'RIDER', 'roles' => ['ROLE_ADMIN']],
            'app_admin_vehicles_delete' => ['action' => 'DELETE', 'type' => 'VEHICLE', 'roles' => ['ROLE_ADMIN']],
            'app_admin_vehicles_edit' => ['action' => 'UPDATE', 'type' => 'VEHICLE', 'roles' => ['ROLE_ADMIN']],
            
            // STAFF: Bookings
            'app_staff_booking_create' => ['action' => 'CREATE', 'type' => 'BOOKING', 'roles' => ['ROLE_STAFF']],
            'app_staff_booking_edit' => ['action' => 'UPDATE', 'type' => 'BOOKING', 'roles' => ['ROLE_STAFF']],
            'app_staff_booking_delete' => ['action' => 'DELETE', 'type' => 'BOOKING', 'roles' => ['ROLE_STAFF']],
            
            // STAFF: Shipments
            'app_staff_shipment_create' => ['action' => 'CREATE', 'type' => 'SHIPMENT', 'roles' => ['ROLE_STAFF']],
            'app_staff_shipment_edit' => ['action' => 'UPDATE', 'type' => 'SHIPMENT', 'roles' => ['ROLE_STAFF']],
            'app_staff_shipment_delete' => ['action' => 'DELETE', 'type' => 'SHIPMENT', 'roles' => ['ROLE_STAFF']],
            
            // STAFF: Complaints
            'app_staff_complaint_create' => ['action' => 'CREATE', 'type' => 'COMPLAINT', 'roles' => ['ROLE_STAFF']],
            'app_staff_complaint_edit' => ['action' => 'UPDATE', 'type' => 'COMPLAINT', 'roles' => ['ROLE_STAFF']],
            'app_staff_complaint_delete' => ['action' => 'DELETE', 'type' => 'COMPLAINT', 'roles' => ['ROLE_STAFF']],
            
            // STAFF: Riders
            'app_staff_rider_create' => ['action' => 'CREATE', 'type' => 'RIDER', 'roles' => ['ROLE_STAFF']],
            'app_staff_rider_edit' => ['action' => 'UPDATE', 'type' => 'RIDER', 'roles' => ['ROLE_STAFF']],
            
            // STAFF: Vehicles
            'app_staff_vehicle_create' => ['action' => 'CREATE', 'type' => 'VEHICLE', 'roles' => ['ROLE_STAFF']],
            'app_staff_vehicle_edit' => ['action' => 'UPDATE', 'type' => 'VEHICLE', 'roles' => ['ROLE_STAFF']],
        ];

        // Check if current route is loggable
        if (!isset($loggableActions[$route])) {
            return;
        }

        $actionConfig = $loggableActions[$route];
        
        // Check if user has required role
        $userRoles = $user->getRoles();
        $hasRequiredRole = false;
        foreach ($actionConfig['roles'] as $requiredRole) {
            if (in_array($requiredRole, $userRoles)) {
                $hasRequiredRole = true;
                break;
            }
        }

        if (!$hasRequiredRole) {
            return;
        }

        // Only log POST/PUT/DELETE requests
        if (!in_array($method, ['POST', 'PUT', 'DELETE'])) {
            return;
        }

        $this->logActivity(
            $user,
            $actionConfig['action'] . '_' . $actionConfig['type'],
            $actionConfig['type'],
            $request
        );
    }

    private function logActivity(User $user, string $action, string $entityType, $request): void
    {
        try {
            $log = new ActivityLog();
            $log->setUserId($user->getId());
            $log->setUsername($user->getUserIdentifier());
            $log->setRole(implode(', ', $user->getRoles()));
            $log->setAction($action);
            
            // Get the affected entity ID
            $targetId = $request->attributes->get('id');
            
            // Format target data in human-readable format
            if ($targetId) {
                // Format: "EntityType: ID: {id}"
                $targetData = sprintf('%s: ID: %d', $entityType, $targetId);
                $log->setTargetData($targetData);
            } else {
                $log->setTargetData($entityType);
            }
            
            $log->setDateTime(new \DateTime());
            
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Silently fail logging to not disrupt the application
            error_log('Failed to log activity: ' . $e->getMessage());
        }
    }
}
