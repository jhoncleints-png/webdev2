<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Order;
use App\Entity\Product;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository,
        private FcmService $fcmService
    ) {}

    /**
     * Create a new notification
     */
    public function create(
        string $type,
        string $title,
        string $message,
        ?string $targetEntity = null,
        ?int $targetId = null
    ): Notification {
        $notification = new Notification();
        $notification->setType($type);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setTargetEntity($targetEntity);
        $notification->setTargetId($targetId);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Create notification for new order
     */
    public function notifyNewOrder(Order $order): void
    {
        $customerName = $order->getCustomer()->getName() ?? $order->getCustomer()->getEmail();
        $totalAmount = number_format($order->getTotalAmount(), 2);
        
        $this->create(
            Notification::TYPE_NEW_ORDER,
            'New Order Received',
            "Order #{$order->getId()} from {$customerName} for ₱{$totalAmount}",
            'Order',
            $order->getId()
        );
    }

    /**
     * Create notification for low stock
     */
    public function notifyLowStock(Product $product): void
    {
        $this->create(
            Notification::TYPE_LOW_STOCK,
            'Low Stock Alert',
            "Product '{$product->getName()}' is running low on stock ({$product->getStockQuantity()} units remaining)",
            'Product',
            $product->getId()
        );
    }

    /**
     * Create notification for cancelled order
     */
    public function notifyOrderCancelled(Order $order): void
    {
        
        $customerName = $order->getCustomer()->getName() ?? $order->getCustomer()->getEmail();
        
        $this->create(
            Notification::TYPE_ORDER_CANCELLED,
            'Order Cancelled',
            "Order #{$order->getId()} from {$customerName} has been cancelled",
            'Order',
            $order->getId()
        );
    }

    /**
     * Create notification for completed order
     */
    public function notifyOrderCompleted(Order $order): void
    {
        
        $customerName = $order->getCustomer()->getName() ?? $order->getCustomer()->getEmail();
        
        $this->create(
            Notification::TYPE_ORDER_COMPLETED,
            'Order Completed',
            "Order #{$order->getId()} from {$customerName} has been completed",
            'Order',
            $order->getId()
        );
    }

    /**
     * Check for low stock products and create notifications
     */
    public function checkLowStock(int $threshold = 10): void
    {
        $productRepository = $this->entityManager->getRepository(Product::class);
        $lowStockProducts = $productRepository->createQueryBuilder('p')
            ->where('p.stockQuantity <= :threshold')
            ->andWhere('p.stockQuantity > 0')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();

        foreach ($lowStockProducts as $product) {
            // Check if we already have a recent notification for this product
            $recentNotification = $this->notificationRepository->createQueryBuilder('n')
                ->where('n.type = :type')
                ->andWhere('n.targetEntity = :entity')
                ->andWhere('n.targetId = :targetId')
                ->andWhere('n.createdAt > :date')
                ->setParameter('type', Notification::TYPE_LOW_STOCK)
                ->setParameter('entity', 'Product')
                ->setParameter('targetId', $product->getId())
                ->setParameter('date', new \DateTime('-24 hours'))
                ->getQuery()
                ->getOneOrNull();

            if (!$recentNotification) {
                $this->notifyLowStock($product);
            }
        }
    }

    /**
     * Get unread notifications
     */
    public function getUnreadNotifications(): array
    {
        return $this->notificationRepository->findUnread();
    }

    /**
     * Get all notifications
     */
    public function getAllNotifications(): array
    {
        return $this->notificationRepository->findAllOrdered();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): void
    {
        $this->notificationRepository->markAsRead($notification);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): int
    {
        return $this->notificationRepository->markAllAsRead();
    }

    /**
     * Count unread notifications
     */
    public function countUnread(): int
    {
        return $this->notificationRepository->countUnread();
    }

    /**
     * Get notification by ID
     */
    public function getNotificationById(int $id): ?Notification
    {
        return $this->notificationRepository->find($id);
    }

    /**
     * Send FCM notification for order status update
     */
    public function sendOrderStatusUpdateNotification(Order $order): void
    {
        try {
            $customer = $order->getCustomer();
            if (!$customer || !$customer->getFcmToken()) {
                error_log('[NOTIFICATION SERVICE] No FCM token found for customer: ' . ($customer ? $customer->getName() : 'unknown'));
                return;
            }

            error_log('[NOTIFICATION SERVICE] Sending FCM notification for order update to customer: ' . $customer->getName());
            $result = $this->fcmService->sendOrderStatusNotification(
                $customer->getFcmToken(),
                $order->getOrderNumber(),
                $order->getStatus(),
                $customer->getName()
            );

            if ($result) {
                error_log('[NOTIFICATION SERVICE] FCM notification sent successfully');
            } else {
                error_log('[NOTIFICATION SERVICE] FCM notification failed to send');
            }
        } catch (\Exception $e) {
            error_log('[NOTIFICATION SERVICE] Error sending FCM notification: ' . $e->getMessage());
        }
    }

    /**
     * Send FCM notification for new order
     */
    public function sendNewOrderNotification(Order $order): void
    {
        try {
            $customer = $order->getCustomer();
            if (!$customer || !$customer->getFcmToken()) {
                error_log('[NOTIFICATION SERVICE] No FCM token found for customer');
                return;
            }

            error_log('[NOTIFICATION SERVICE] Sending FCM notification for new order to customer: ' . $customer->getName());
            $result = $this->fcmService->sendNotification(
                $customer->getFcmToken(),
                'New Order Confirmation',
                "Your order #{$order->getOrderNumber()} has been received. Total: ₱" . number_format($order->getTotalAmount(), 2),
                [
                    'type' => 'order_update',
                    'orderNumber' => $order->getOrderNumber(),
                    'orderId' => $order->getId(),
                    'status' => $order->getStatus(),
                ]
            );

            if ($result) {
                error_log('[NOTIFICATION SERVICE] FCM new order notification sent successfully');
            }
        } catch (\Exception $e) {
            error_log('[NOTIFICATION SERVICE] Error sending new order FCM notification: ' . $e->getMessage());
        }
    }
}