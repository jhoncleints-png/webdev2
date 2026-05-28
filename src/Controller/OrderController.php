<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Service\ActivityLogger;
use App\Util\DecimalMath;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/order')]
#[IsGranted('ROLE_STAFF')]
final class OrderController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger,
        private HubInterface $mercureHub
    ) {}

    /**
     * Deduct stock when order is created
     */
    private function deductStock(Order $order, EntityManagerInterface $entityManager): array
    {
        $errors = [];
        $stockUpdates = [];

        foreach ($order->getOrderItems() as $item) {
            $product = $item->getProduct();
            $quantity = $item->getQuantity();

            // Check if enough stock is available
            if ($product->getStockQuantity() < $quantity) {
                $errors[] = "Not enough stock for {$product->getName()}. Available: {$product->getStockQuantity()}, Requested: {$quantity}";
            } else {
                // Deduct stock
                $oldStock = $product->getStockQuantity();
                $product->setStockQuantity($oldStock - $quantity);
                $product->setLastStockUpdate(new \DateTime());

                $stockUpdates[] = "{$product->getName()}: {$oldStock} → {$product->getStockQuantity()}";
            }
        }

        return ['errors' => $errors, 'updates' => $stockUpdates];
    }

    /**
     * Restore stock when order is cancelled or deleted
     */
    private function restoreStock(Order $order, EntityManagerInterface $entityManager): array
    {
        $stockUpdates = [];

        foreach ($order->getOrderItems() as $item) {
            $product = $item->getProduct();
            $oldStock = $product->getStockQuantity();
            $product->setStockQuantity($oldStock + $item->getQuantity());
            $product->setLastStockUpdate(new \DateTime());

            $stockUpdates[] = "{$product->getName()}: {$oldStock} → {$product->getStockQuantity()}";
        }

        return $stockUpdates;
    }

    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Remove any empty items before saving
            foreach ($order->getOrderItems() as $item) {
                if (!$item->getProduct()) {
                    $order->removeOrderItem($item);
                    $entityManager->remove($item);
                }
            }

            // Check if there are any order items
            if ($order->getOrderItems()->isEmpty()) {
                $this->addFlash('error', 'Please add at least one item to the order.');
                $products = $entityManager->getRepository(Product::class)->findAll();
                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form->createView(),
                    'products' => $products,
                ]);
            }

            // Check stock availability and deduct
            $stockResult = $this->deductStock($order, $entityManager);
            
            if (!empty($stockResult['errors'])) {
                foreach ($stockResult['errors'] as $error) {
                    $this->addFlash('error', $error);
                }
                $products = $entityManager->getRepository(Product::class)->findAll();
                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form->createView(),
                    'products' => $products,
                ]);
            }

            // Calculate total and set unit prices
            $total = '0.00';
            foreach ($order->getOrderItems() as $item) {
                if (!$item->getUnitPrice() && $item->getProduct()) {
                    $item->setUnitPrice((string)$item->getProduct()->getPrice());
                }

                $itemTotal = DecimalMath::mul($item->getUnitPrice(), (string) $item->getQuantity(), 2);
                $total = DecimalMath::add($total, $itemTotal, 2);
            }

            $order->setTotalAmount($total);
            $order->setCreatedBy($this->getUser());

            $entityManager->persist($order);
            $entityManager->flush();

            // Publish to Mercure for real-time notifications (optional)
            try {
                $update = new Update(
                    'orders/new',
                    json_encode([
                        'id' => $order->getId(),
                        'orderNumber' => $order->getOrderNumber(),
                        'customer' => $order->getCustomer()->getEmail(),
                        'customerName' => $order->getCustomer()->getName(),
                        'total' => $order->getTotalAmount(),
                        'status' => $order->getStatus(),
                        'items' => array_map(function($item) {
                            return [
                                'productName' => $item->getProduct()->getName(),
                                'quantity' => $item->getQuantity(),
                                'price' => $item->getUnitPrice()
                            ];
                        }, $order->getOrderItems()->toArray()),
                        'createdAt' => $order->getOrderDate()->format('Y-m-d H:i:s')
                    ])
                );
                $this->mercureHub->publish($update);
            } catch (\Exception $e) {
                // Mercure is optional, don't fail if it's not available
                error_log('Mercure publish failed: ' . $e->getMessage());
            }

            // LOG ORDER CREATION
            $this->activityLogger->log(
                $this->getUser(),
                'CREATE_ORDER',
                'Order',
                $order->getId(),
                "Created order: {$order->getOrderNumber()} for customer: {$order->getCustomer()->getName()} (ID: {$order->getId()}, Total: \${$order->getTotalAmount()})"
            );

            // LOG STOCK DEDUCTIONS
            foreach ($stockResult['updates'] as $update) {
                $this->activityLogger->log(
                    $this->getUser(),
                    'STOCK_DEDUCT',
                    'Product',
                    null,
                    $update
                );
            }

            $this->addFlash('success', 'Order created successfully! Stock has been deducted.');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        // Get products for the template dropdown
        $products = $entityManager->getRepository(Product::class)->findAll();

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
            'products' => $products,
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        // Prevent editing of delivered orders
        if ($order->getStatus() === Order::STATUS_DELIVERED) {
            $this->addFlash('error', 'Cannot edit delivered orders.');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        // Staff can only edit their own orders
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            if ($order->getCreatedBy()->getId() !== $this->getUser()->getId()) {
                $this->addFlash('error', 'You can only edit your own orders.');
                return $this->redirectToRoute('app_order_index');
            }
        }

        $oldStatus = $order->getStatus();
        $oldOrderItems = clone $order->getOrderItems(); // Store old items for stock restoration

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // If order is being cancelled, restore stock
            if ($oldStatus !== Order::STATUS_CANCELLED && $order->getStatus() === Order::STATUS_CANCELLED) {
                $stockUpdates = $this->restoreStock($order, $entityManager);
                foreach ($stockUpdates as $update) {
                    $this->activityLogger->log(
                        $this->getUser(),
                        'STOCK_RESTORE',
                        'Product',
                        null,
                        $update . " (Order cancelled)"
                    );
                }
                $this->addFlash('info', 'Order cancelled. Stock has been restored.');
            }

            $entityManager->flush();

            // Publish status update to Mercure (optional)
            try {
                $update = new Update(
                    'orders/update',
                    json_encode([
                        'id' => $order->getId(),
                        'orderNumber' => $order->getOrderNumber(),
                        'status' => $order->getStatus(),
                        'customerName' => $order->getCustomer()->getName(),
                        'updatedAt' => $order->getOrderDate()->format('Y-m-d H:i:s')
                    ])
                );
                $this->mercureHub->publish($update);
            } catch (\Exception $e) {
                // Mercure is optional, don't fail if it's not available
                error_log('Mercure publish failed: ' . $e->getMessage());
            }

            // LOG ORDER UPDATE
            $newStatus = $order->getStatus();
            $statusChanged = $oldStatus !== $newStatus;

            $description = "Updated order: {$order->getOrderNumber()} (ID: {$order->getId()})";
            if ($statusChanged) {
                $description .= " - Status changed from {$oldStatus} to {$newStatus}";
            }

            $this->activityLogger->log(
                $this->getUser(),
                'UPDATE_ORDER',
                'Order',
                $order->getId(),
                $description
            );

            $this->addFlash('success', 'Order updated successfully!');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $orderNumber = $order->getOrderNumber();
            $orderId = $order->getId();
            $customerName = $order->getCustomer()->getName();

            // Restore stock before deleting if order wasn't cancelled
            if ($order->getStatus() !== Order::STATUS_CANCELLED) {
                $stockUpdates = $this->restoreStock($order, $entityManager);
                foreach ($stockUpdates as $update) {
                    $this->activityLogger->log(
                        $this->getUser(),
                        'STOCK_RESTORE',
                        'Product',
                        null,
                        $update . " (Order deleted)"
                    );
                }
            }

            $entityManager->remove($order);
            $entityManager->flush();

            // LOG ORDER DELETION
            $this->activityLogger->log(
                $this->getUser(),
                'DELETE_ORDER',
                'Order',
                $orderId,
                "Deleted order: {$orderNumber} for customer: {$customerName} (ID: {$orderId})"
            );

            $this->addFlash('success', 'Order deleted successfully! Stock has been restored.');
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }
}