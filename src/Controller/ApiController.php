<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles()
            ]
        ]);
    }

    #[Route('/api/products', name: 'api_products', methods: ['GET'])]
    public function products(ProductRepository $productRepository): JsonResponse
    {
        $products = $productRepository->findAll();
        
        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
                'stockQuantity' => $product->getStockQuantity(),
                'minimumStock' => $product->getMinimumStock(),
                'category' => $product->getCategory() ? [
                    'id' => $product->getCategory()->getId(),
                    'name' => $product->getCategory()->getName(),
                ] : null,
                'createdAt' => $product->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json([
            'products' => $data,
            'count' => count($data)
        ]);
    }

    #[Route('/api/orders', name: 'api_orders', methods: ['GET'])]
    public function orders(OrderRepository $orderRepository): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        $orders = $orderRepository->findBy(['createdBy' => $user], ['orderDate' => 'DESC']);
        
        $data = [];
        foreach ($orders as $order) {
            $items = [];
            foreach ($order->getOrderItems() as $item) {
                $items[] = [
                    'productName' => $item->getProduct()->getName(),
                    'quantity' => $item->getQuantity(),
                    'unitPrice' => $item->getUnitPrice(),
                ];
            }

            $data[] = [
                'id' => $order->getId(),
                'orderNumber' => $order->getOrderNumber(),
                'orderDate' => $order->getOrderDate()->format('Y-m-d H:i:s'),
                'status' => $order->getStatus(),
                'totalAmount' => $order->getTotalAmount(),
                'notes' => $order->getNotes(),
                'customer' => [
                    'id' => $order->getCustomer()->getId(),
                    'name' => $order->getCustomer()->getName(),
                    'email' => $order->getCustomer()->getEmail(),
                ],
                'items' => $items,
            ];
        }

        return $this->json([
            'orders' => $data,
            'count' => count($data)
        ]);
    }

    #[Route('/api/categories', name: 'api_categories', methods: ['GET'])]
    public function categories(CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findAll();
        
        $data = [];
        foreach ($categories as $category) {
            $products = [];
            foreach ($category->getProducts() as $product) {
                $products[] = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                ];
            }

            $data[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'description' => $category->getDescription(),
                'productCount' => count($products),
                'products' => $products,
            ];
        }

        return $this->json([
            'categories' => $data,
            'count' => count($data)
        ]);
    }
}