<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stock')]
class StockController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route('/', name: 'app_stock_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $products = $entityManager->getRepository(Product::class)->findAll();

        return $this->render('stock/index.html.twig', [
            'products' => $products,
        ]);
    }
    
    #[Route('/{id}/adjust', name: 'app_stock_adjust', methods: ['GET', 'POST'])]
    public function adjust(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $quantity = $request->request->getInt('quantity');
            $action = $request->request->get('action');
            $oldQuantity = $product->getStockQuantity();
            
            if ($action === 'add') {
                $product->setStockQuantity($product->getStockQuantity() + $quantity);
                $this->activityLogger->log(
                    $this->getUser(),
                    'STOCK_ADD',
                    'Product',
                    $product->getId(),
                    "Added {$quantity} units to {$product->getName()}. Stock: {$oldQuantity} → {$product->getStockQuantity()}"
                );
            } elseif ($action === 'remove') {
                $newQuantity = $product->getStockQuantity() - $quantity;
                if ($newQuantity >= 0) {
                    $product->setStockQuantity($newQuantity);
                    $this->activityLogger->log(
                        $this->getUser(),
                        'STOCK_REMOVE',
                        'Product',
                        $product->getId(),
                        "Removed {$quantity} units from {$product->getName()}. Stock: {$oldQuantity} → {$newQuantity}"
                    );
                } else {
                    $this->addFlash('error', 'Cannot remove more than available stock!');
                    return $this->redirectToRoute('app_stock_index');
                }
            } elseif ($action === 'set') {
                $product->setStockQuantity($quantity);
                $this->activityLogger->log(
                    $this->getUser(),
                    'STOCK_SET',
                    'Product',
                    $product->getId(),
                    "Set stock of {$product->getName()} to {$quantity} (was {$oldQuantity})"
                );
            }
            
            $product->setLastStockUpdate(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Stock updated successfully!');
            return $this->redirectToRoute('app_stock_index');
        }
        
        return $this->render('stock/adjust.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/low-stock', name: 'app_stock_low', methods: ['GET'])]
    public function lowStock(EntityManagerInterface $entityManager): Response
    {
        $products = $entityManager->getRepository(Product::class)->findAll();
        $lowStockProducts = [];

        foreach ($products as $product) {
            $minStock = $product->getMinimumStock();
            if ($minStock !== null && $product->getStockQuantity() <= $minStock) {
                $lowStockProducts[] = $product;
            }
        }
        
        return $this->render('stock/low_stock.html.twig', [
            'products' => $lowStockProducts,
        ]);
    }
}