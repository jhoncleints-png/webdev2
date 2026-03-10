<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/order')]
#[IsGranted('ROLE_STAFF')] // Both admin and staff can access
final class OrderController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

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
            
            // Calculate total and set unit prices
            $total = '0.00';
            foreach ($order->getOrderItems() as $item) {
                if (!$item->getUnitPrice() && $item->getProduct()) {
                    $item->setUnitPrice((string)$item->getProduct()->getPrice());
                }
                
                $itemTotal = bcmul($item->getUnitPrice(), (string)$item->getQuantity(), 2);
                $total = bcadd($total, $itemTotal, 2);
            }
            
            $order->setTotalAmount($total);
            $order->setCreatedBy($this->getUser());
            
            $entityManager->persist($order);
            $entityManager->flush();

            // LOG ORDER CREATION
            $this->activityLogger->log(
                $this->getUser(),
                'CREATE_ORDER',
                'Order',
                $order->getId(),
                "Created order: {$order->getOrderNumber()} for customer: {$order->getCustomer()->getName()} (ID: {$order->getId()}, Total: \${$order->getTotalAmount()})"
            );

            $this->addFlash('success', 'Order created successfully!');
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
        // Prevent editing of delivered or cancelled orders
        if (in_array($order->getStatus(), [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])) {
            $this->addFlash('error', 'Cannot edit orders that are delivered or cancelled.');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }
        
        // ADD THIS CHECK: Staff can only edit their own orders
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            if ($order->getCreatedBy()->getId() !== $this->getUser()->getId()) {
                $this->addFlash('error', 'You can only edit your own orders.');
                return $this->redirectToRoute('app_order_index');
            }
        }
        
        $oldStatus = $order->getStatus();
        
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

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
    #[IsGranted('ROLE_ADMIN')] // Only admin can delete orders
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $orderNumber = $order->getOrderNumber();
            $orderId = $order->getId();
            $customerName = $order->getCustomer()->getName();
            
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

            $this->addFlash('success', 'Order deleted successfully!');
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }
}