<?php

namespace App\Controller;

use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    #[Route('', name: 'api_notifications_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(): JsonResponse
    {
        $notifications = $this->notificationService->getAllNotifications();
        
        $data = array_map(function ($notification) {
            return [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'targetEntity' => $notification->getTargetEntity(),
                'targetId' => $notification->getTargetId(),
                'isRead' => $notification->isRead(),
                'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $notifications);

        return $this->json([
            'notifications' => $data,
            'unreadCount' => $this->notificationService->countUnread(),
        ]);
    }

    #[Route('/unread', name: 'api_notifications_unread', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function unread(): JsonResponse
    {
        $notifications = $this->notificationService->getUnreadNotifications();
        
        $data = array_map(function ($notification) {
            return [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'targetEntity' => $notification->getTargetEntity(),
                'targetId' => $notification->getTargetId(),
                'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $notifications);

        return $this->json([
            'notifications' => $data,
            'unreadCount' => count($data),
        ]);
    }

    #[Route('/{id}/read', name: 'api_notifications_mark_read', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function markAsRead(int $id): JsonResponse
    {
        $notification = $this->notificationService->getNotificationById($id);
        
        if (!$notification) {
            return $this->json(['error' => 'Notification not found'], 404);
        }

        $this->notificationService->markAsRead($notification);

        return $this->json([
            'success' => true,
            'unreadCount' => $this->notificationService->countUnread(),
        ]);
    }

    #[Route('/mark-all-read', name: 'api_notifications_mark_all_read', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function markAllAsRead(): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead();

        return $this->json([
            'success' => true,
            'markedCount' => $count,
            'unreadCount' => 0,
        ]);
    }

    #[Route('/count', name: 'api_notifications_count', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function count(): JsonResponse
    {
        return $this->json([
            'unreadCount' => $this->notificationService->countUnread(),
        ]);
    }
}
