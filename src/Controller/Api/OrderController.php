<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders')]
class OrderController extends AbstractController
{
    #[Route('', name: 'api_orders_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $customerId = $data['customer_id'] ?? null;
        $items = $data['items'] ?? [];

        if (!$customerId || !is_array($items) || count($items) === 0) {
            return $this->json(['error' => 'Missing customer_id or items'], Response::HTTP_BAD_REQUEST);
        }

        $customer = $em->getRepository(Customer::class)->find($customerId);
        if (!$customer) {
            return $this->json(['error' => 'Customer not found'], Response::HTTP_BAD_REQUEST);
        }

        $order = new Order();
        $order->setCustomer($customer);
        $order->setCreatedBy($user);

        $stockErrors = [];

        foreach ($items as $it) {
            $productId = $it['product_id'] ?? null;
            $quantity = (int)($it['quantity'] ?? 0);

            if (!$productId || $quantity <= 0) {
                return $this->json(['error' => 'Invalid item payload'], Response::HTTP_BAD_REQUEST);
            }

            $product = $em->getRepository(Product::class)->find($productId);
            if (!$product) {
                return $this->json(['error' => "Product {$productId} not found"], Response::HTTP_BAD_REQUEST);
            }

            if ($product->getStockQuantity() < $quantity) {
                $stockErrors[] = "Not enough stock for {$product->getName()}";
                continue;
            }

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($quantity);
            // Use product price as unit price
            $orderItem->setUnitPrice((string)$product->getPrice());
            $order->addOrderItem($orderItem);

            // Deduct stock immediately
            $product->setStockQuantity($product->getStockQuantity() - $quantity);
            $product->setLastStockUpdate(new \DateTime());
            $em->persist($product);
        }

        if (!empty($stockErrors)) {
            return $this->json(['errors' => $stockErrors], Response::HTTP_BAD_REQUEST);
        }

        // Calculate total and persist
        $order->setTotalAmount($order->calculateTotal());
        $em->persist($order);
        $em->flush();

        return $this->json([
            'id' => $order->getId(),
            'order_number' => $order->getOrderNumber(),
            'total' => $order->getTotalAmount(),
        ], Response::HTTP_CREATED);
    }
}
