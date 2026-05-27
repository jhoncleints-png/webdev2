<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Service\CustomerResolver;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\ActivityLogRepository;
use Doctrine\DBAL\Exception as DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    private function createErrorResponse(string $message, int $code, ?string $errorCode = null): JsonResponse
    {
        $response = [
            'error' => $message,
            'code' => $code,
        ];
        
        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }
        
        return $this->json($response, $code);
    }

    private function publishUpdate(string $topic, array $data, HubInterface $hub): void
    {
        $update = new Update(
            $topic,
            json_encode($data)
        );
        $hub->publish($update);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(CustomerResolver $customerResolver): JsonResponse
    {
        try {
            $user = $this->getUser();
            
            if (!$user instanceof User) {
                return $this->createErrorResponse('Not authenticated', 401, 'AUTH_REQUIRED');
            }

            $customer = $customerResolver->resolveForUser($user);

            return $this->json([
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'fullName' => $user->getFullName(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'roles' => $user->getRoles(),
                    'isVerified' => $user->isVerified(),
                    'customer' => [
                        'id' => $customer->getId(),
                        'email' => $customer->getEmail(),
                        'fullName' => $customer->getName(),
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse('An error occurred while fetching user profile', 500, 'SERVER_ERROR');
        }
    }

    #[Route('/api/products', name: 'api_products', methods: ['GET'])]
    public function products(ProductRepository $productRepository): JsonResponse
    {
        try {
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
                    'isMixedDrink' => $product->isMixedDrink(),
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
        } catch (DBALException $e) {
            return $this->createErrorResponse('Database error while fetching products: ' . $e->getMessage(), 500, 'DATABASE_ERROR');
        } catch (\Throwable $e) {
            return $this->createErrorResponse('An error occurred while fetching products: ' . $e->getMessage(), 500, 'SERVER_ERROR');
        }
    }

    #[Route('/api/orders', name: 'api_orders_create', methods: ['POST'])]
    public function createOrder(
        Request $request,
        EntityManagerInterface $entityManager,
        CustomerResolver $customerResolver
    ): JsonResponse {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->createErrorResponse('Not authenticated', 401, 'AUTH_REQUIRED');
            }

            $data = json_decode($request->getContent(), true) ?? [];
            $items = $data['items'] ?? [];

            if (!is_array($items) || count($items) === 0) {
                return $this->createErrorResponse('Missing order items', 400, 'INVALID_PAYLOAD');
            }

            $customerId = $data['customer_id'] ?? $data['customerId'] ?? null;
            if ($customerId) {
                $customer = $entityManager->getRepository(Customer::class)->find($customerId);
                if (!$customer || $customer->getEmail() !== $user->getEmail()) {
                    return $this->createErrorResponse('Customer not found', 400, 'CUSTOMER_NOT_FOUND');
                }
            } else {
                $customer = $customerResolver->resolveForUser($user);
            }

            $order = new Order();
            $order->setCustomer($customer);
            $order->setCreatedBy($user);

            $stockErrors = [];
            $lineItems = [];

            foreach ($items as $item) {
                $productId = $item['product_id'] ?? $item['productId'] ?? null;
                $quantity = (int) ($item['quantity'] ?? 0);

                if (!$productId || $quantity <= 0) {
                    return $this->createErrorResponse('Invalid item payload', 400, 'INVALID_ITEM');
                }

                $product = $entityManager->getRepository(Product::class)->find($productId);
                if (!$product) {
                    return $this->createErrorResponse("Product {$productId} not found", 400, 'PRODUCT_NOT_FOUND');
                }

                if ($product->getStockQuantity() < $quantity) {
                    $stockErrors[] = "Not enough stock for {$product->getName()}";
                    continue;
                }

                $orderItem = new OrderItem();
                $orderItem->setProduct($product);
                $orderItem->setQuantity($quantity);
                $orderItem->setUnitPrice(number_format((float) $product->getPrice(), 2, '.', ''));

                $lineItems[] = [
                    'product' => $product,
                    'orderItem' => $orderItem,
                    'quantity' => $quantity,
                ];
            }

            if (!empty($stockErrors)) {
                return $this->json(['error' => implode('; ', $stockErrors), 'errors' => $stockErrors], 400);
            }

            if (count($lineItems) === 0) {
                return $this->createErrorResponse('No valid order items', 400, 'INVALID_PAYLOAD');
            }

            $notes = $data['notes'] ?? null;
            if (is_string($notes) && $notes !== '') {
                $order->setNotes($notes);
            }

            // FIXED: Use Doctrine's transactional method instead of manual beginTransaction/commit
            try {
                $entityManager->wrapInTransaction(function() use ($entityManager, $lineItems, $order) {
                    foreach ($lineItems as $line) {
                        $order->addOrderItem($line['orderItem']);

                        $updatedRows = $entityManager->createQuery(
                            'UPDATE App\Entity\Product p
                            SET p.stockQuantity = p.stockQuantity - :quantity,
                                p.lastStockUpdate = :lastStockUpdate
                            WHERE p.id = :id AND p.stockQuantity >= :quantity'
                        )
                            ->setParameter('quantity', $line['quantity'])
                            ->setParameter('lastStockUpdate', new \DateTime())
                            ->setParameter('id', $line['product']->getId())
                            ->execute();

                        if ($updatedRows !== 1) {
                            throw new \RuntimeException('Not enough stock for ' . $line['product']->getName());
                        }

                        $entityManager->refresh($line['product']);
                    }

                    $order->setTotalAmount($order->calculateTotal());
                    $entityManager->persist($order);
                });
            } catch (\Throwable $transactionError) {
                throw $transactionError;
            }

            // Create notification for new order (outside transaction)
            $this->notificationService->notifyNewOrder($order);

            // Check for low stock after order
            foreach ($lineItems as $line) {
                if ($line['product']->getStockQuantity() <= 10) {
                    $this->notificationService->notifyLowStock($line['product']);
                }
            }

            return $this->json([
                'id' => $order->getId(),
                'orderNumber' => $order->getOrderNumber(),
                'orderDate' => $order->getOrderDate()->format('Y-m-d H:i:s'),
                'status' => $order->getStatus(),
                'totalAmount' => $order->getTotalAmount(),
                'customer' => [
                    'id' => $customer->getId(),
                    'name' => $customer->getName(),
                    'email' => $customer->getEmail(),
                ],
            ], 201);
        } catch (DBALException $e) {
            return $this->createErrorResponse('Database error while creating order: ' . $e->getMessage(), 500, 'DATABASE_ERROR');
        } catch (\Throwable $e) {
            return $this->createErrorResponse('An error occurred while creating order: ' . $e->getMessage(), 500, 'SERVER_ERROR');
        }
    }

    #[Route('/api/orders', name: 'api_orders', methods: ['GET'])]
    public function orders(OrderRepository $orderRepository): JsonResponse
    {
        try {
            $user = $this->getUser();

            if (!$user instanceof User) {
                return $this->createErrorResponse('Not authenticated', 401, 'AUTH_REQUIRED');
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
        } catch (DBALException $e) {
            return $this->createErrorResponse('Database error while fetching orders: ' . $e->getMessage(), 500, 'DATABASE_ERROR');
        } catch (\Throwable $e) {
            return $this->createErrorResponse('An error occurred while fetching orders: ' . $e->getMessage(), 500, 'SERVER_ERROR');
        }
    }

    #[Route('/api/categories', name: 'api_categories', methods: ['GET'])]
    public function categories(CategoryRepository $categoryRepository): JsonResponse
    {
        try {
            $categories = $categoryRepository->findAll();
            
            $data = [];
            foreach ($categories as $category) {
                $products = [];
                foreach ($category->getProducts() as $product) {
                    $products[] = [
                        'id' => $product->getId(),
                        'name' => $product->getName(),
                        'price' => $product->getPrice(),
                        'isMixedDrink' => $product->isMixedDrink(),
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
        } catch (DBALException $e) {
            return $this->createErrorResponse('Database error while fetching categories', 500, 'DATABASE_ERROR');
        } catch (\Exception $e) {
            return $this->createErrorResponse('An error occurred while fetching categories', 500, 'SERVER_ERROR');
        }
    }

    #[Route('/api/products/{id}', name: 'api_product_show', methods: ['GET'])]
    public function productShow(int $id, ProductRepository $productRepository): JsonResponse
    {
        try {
            $product = $productRepository->find($id);
            
            if (!$product) {
                return $this->createErrorResponse('Product not found', 404, 'NOT_FOUND');
            }

            return $this->json([
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
                'stockQuantity' => $product->getStockQuantity(),
                'minimumStock' => $product->getMinimumStock(),
                'isMixedDrink' => $product->isMixedDrink(),
                'category' => $product->getCategory() ? [
                    'id' => $product->getCategory()->getId(),
                    'name' => $product->getCategory()->getName(),
                    'description' => $product->getCategory()->getDescription(),
                ] : null,
                'createdAt' => $product->getCreatedAt()?->format('Y-m-d H:i:s'),
            ]);
        } catch (DBALException $e) {
            return $this->createErrorResponse('Database error while fetching product', 500, 'DATABASE_ERROR');
        } catch (\Exception $e) {
            return $this->createErrorResponse('An error occurred while fetching product', 500, 'SERVER_ERROR');
        }
    }

    #[Route('/api/orders/{id}', name: 'api_order_show', methods: ['GET'])]
    public function orderShow(int $id, OrderRepository $orderRepository): JsonResponse
    {
        try {
            $user = $this->getUser();
            
            if (!$user) {
                return $this->createErrorResponse('Not authenticated', 401, 'AUTH_REQUIRED');
            }

            $order = $orderRepository->find($id);
            
            if (!$order) {
                return $this->createErrorResponse('Order not found', 404, 'NOT_FOUND');
            }

            // Check if user has access to this order
            if ($order->getCreatedBy()->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN')) {
                return $this->createErrorResponse('Access denied to this order', 403, 'ACCESS_DENIED');
            }

            $items = [];
            foreach ($order->getOrderItems() as $item) {
                $items[] = [
                    'id' => $item->getId(),
                    'productName' => $item->getProduct()->getName(),
                    'quantity' => $item->getQuantity(),
                    'unitPrice' => $item->getUnitPrice(),
                    'subtotal' => $item->getItemTotal(),
                ];
            }

            return $this->json([
                'id' => $order->getId(),
                'orderNumber' => $order->getOrderNumber(),
                'orderDate' => $order->getOrderDate()->format('Y-m-d H:i:s'),
                'status' => $order->getStatus(),
                'statusLabel' => $order->getStatusLabel(),
                'totalAmount' => $order->getTotalAmount(),
                'notes' => $order->getNotes(),
                'customer' => [
                    'id' => $order->getCustomer()->getId(),
                    'name' => $order->getCustomer()->getName(),
                    'email' => $order->getCustomer()->getEmail(),
                    'phone' => $order->getCustomer()->getPhone(),
                    'address' => $order->getCustomer()->getAddress(),
                ],
                'items' => $items,
                'itemCount' => count($items),
            ]);
        } catch (DBALException $e) {
            return $this->createErrorResponse('Database error while fetching order', 500, 'DATABASE_ERROR');
        } catch (\Exception $e) {
            return $this->createErrorResponse('An error occurred while fetching order', 500, 'SERVER_ERROR');
        }
    }

    #[Route('/api/categories/{id}', name: 'api_category_show', methods: ['GET'])]
    public function categoryShow(int $id, CategoryRepository $categoryRepository): JsonResponse
    {
        try {
            $category = $categoryRepository->find($id);
            
            if (!$category) {
                return $this->createErrorResponse('Category not found', 404, 'NOT_FOUND');
            }

            $products = [];
            foreach ($category->getProducts() as $product) {
                $products[] = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'description' => $product->getDescription(),
                    'price' => $product->getPrice(),
                    'stockQuantity' => $product->getStockQuantity(),
                    'isMixedDrink' => $product->isMixedDrink(),
                ];
            }

            return $this->json([
                'id' => $category->getId(),
                'name' => $category->getName(),
                'description' => $category->getDescription(),
                'products' => $products,
                'productCount' => count($products),
            ]);
        } catch (DBALException $e) {
            return $this->createErrorResponse('Database error while fetching category', 500, 'DATABASE_ERROR');
        } catch (\Exception $e) {
            return $this->createErrorResponse('An error occurred while fetching category', 500, 'SERVER_ERROR');
        }
    }

    #[Route('/api/sync/orders', name: 'api_sync_orders', methods: ['GET'])]
    public function syncOrders(Request $request, OrderRepository $orderRepository, HubInterface $hub): JsonResponse
    {
        try {
            $user = $this->getUser();
            
            if (!$user) {
                return $this->createErrorResponse('Not authenticated', 401, 'AUTH_REQUIRED');
            }

            $since = $request->query->get('since');
            $sinceDate = $since ? new \DateTime($since) : null;

            $criteria = ['createdBy' => $user];
            if ($sinceDate) {
                $orders = $orderRepository->createQueryBuilder('o')
                    ->where('o.createdBy = :user')
                    ->andWhere('o.orderDate > :since')
                    ->setParameter('user', $user)
                    ->setParameter('since', $sinceDate)
                    ->orderBy('o.orderDate', 'DESC')
                    ->getQuery()
                    ->getResult();
            } else {
                $orders = $orderRepository->findBy($criteria, ['orderDate' => 'DESC']);
            }
            
            $data = [];
            foreach ($orders as $order) {
                $items = [];
                foreach ($order->getOrderItems() as $item) {
                    $items[] = [
                        'id' => $item->getId(),
                        'productName' => $item->getProduct()->getName(),
                        'quantity' => $item->getQuantity(),
                        'unitPrice' => $item->getUnitPrice(),
                        'subtotal' => $item->getItemTotal(),
                    ];
                }

                $data[] = [
                    'id' => $order->getId(),
                    'orderNumber' => $order->getOrderNumber(),
                    'orderDate' => $order->getOrderDate()->format('Y-m-d H:i:s'),
                    'status' => $order->getStatus(),
                    'statusLabel' => $order->getStatusLabel(),
                    'totalAmount' => $order->getTotalAmount(),
                    'customer' => [
                        'id' => $order->getCustomer()->getId(),
                        'name' => $order->getCustomer()->getName(),
                    ],
                    'items' => $items,
                ];
            }

            // Publish to Mercure for real-time updates
            $this->publishUpdate('/orders/' . $user->getId(), [
                'type' => 'orders_sync',
                'data' => $data,
                'count' => count($data),
                'synced_at' => (new \DateTime())->format('Y-m-d H:i:s')
            ], $hub);

            return $this->json([
                'orders' => $data,
                'count' => count($data),
                'synced_at' => (new \DateTime())->format('Y-m-d H:i:s')
            ]);
        } catch (DBALException $e) {
            return $this->createErrorResponse('Database error while syncing orders', 500, 'DATABASE_ERROR');
        } catch (\Exception $e) {
            return $this->createErrorResponse('An error occurred while syncing orders', 500, 'SERVER_ERROR');
        }
    }

    #[Route('/api/sync/products', name: 'api_sync_products', methods: ['GET'])]
    public function syncProducts(Request $request, ProductRepository $productRepository, HubInterface $hub): JsonResponse
    {
        try {
            $since = $request->query->get('since');
            $sinceDate = $since ? new \DateTime($since) : null;

            if ($sinceDate) {
                $products = $productRepository->createQueryBuilder('p')
                    ->where('p.createdAt > :since OR p.lastStockUpdate > :since')
                    ->setParameter('since', $sinceDate)
                    ->getQuery()
                    ->getResult();
            } else {
                $products = $productRepository->findAll();
            }
            
            $data = [];
            foreach ($products as $product) {
                $data[] = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'description' => $product->getDescription(),
                    'price' => $product->getPrice(),
                    'stockQuantity' => $product->getStockQuantity(),
                    'isMixedDrink' => $product->isMixedDrink(),
                    'category' => $product->getCategory() ? [
                        'id' => $product->getCategory()->getId(),
                        'name' => $product->getCategory()->getName(),
                    ] : null,
                    'lastStockUpdate' => $product->getLastStockUpdate()?->format('Y-m-d H:i:s'),
                ];
            }

            // Publish to Mercure for real-time updates
            $this->publishUpdate('/products', [
                'type' => 'products_sync',
                'data' => $data,
                'count' => count($data),
                'synced_at' => (new \DateTime())->format('Y-m-d H:i:s')
            ], $hub);

            return $this->json([
                'products' => $data,
                'count' => count($data),
                'synced_at' => (new \DateTime())->format('Y-m-d H:i:s')
            ]);
        } catch (DBALException $e) {
            return $this->createErrorResponse('Database error while syncing products', 500, 'DATABASE_ERROR');
        } catch (\Exception $e) {
            return $this->createErrorResponse('An error occurred while syncing products', 500, 'SERVER_ERROR');
        }
    }

    #[Route('/api/sync/activity', name: 'api_sync_activity', methods: ['GET'])]
    public function syncActivity(Request $request, ActivityLogRepository $activityLogRepository, HubInterface $hub): JsonResponse
    {
        try {
            $user = $this->getUser();
            
            if (!$user) {
                return $this->createErrorResponse('Not authenticated', 401, 'AUTH_REQUIRED');
            }

            $since = $request->query->get('since');
            $sinceDate = $since ? new \DateTime($since) : null;
            $limit = min((int)$request->query->get('limit', 50), 100);

            $qb = $activityLogRepository->createQueryBuilder('a')
                ->where('a.user = :user')
                ->setParameter('user', $user)
                ->orderBy('a.createdAt', 'DESC')
                ->setMaxResults($limit);

            if ($sinceDate) {
                $qb->andWhere('a.createdAt > :since')
                   ->setParameter('since', $sinceDate);
            }

            $activities = $qb->getQuery()->getResult();
            
            $data = [];
            foreach ($activities as $activity) {
                $data[] = [
                    'id' => $activity->getId(),
                    'action' => $activity->getAction(),
                    'targetEntity' => $activity->getTargetEntity(),
                    'targetId' => $activity->getTargetId(),
                    'details' => $activity->getDetails(),
                    'createdAt' => $activity->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }

            // Publish to Mercure for real-time updates
            $this->publishUpdate('/activity/' . $user->getId(), [
                'type' => 'activity_sync',
                'data' => $data,
                'count' => count($data),
                'synced_at' => (new \DateTime())->format('Y-m-d H:i:s')
            ], $hub);

            return $this->json([
                'activities' => $data,
                'count' => count($data),
                'synced_at' => (new \DateTime())->format('Y-m-d H:i:s')
            ]);
        } catch (DBALException $e) {
            return $this->createErrorResponse('Database error while syncing activity', 500, 'DATABASE_ERROR');
        } catch (\Exception $e) {
            return $this->createErrorResponse('An error occurred while syncing activity', 500, 'SERVER_ERROR');
        }
    }
}