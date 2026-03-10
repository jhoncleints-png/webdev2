<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted; // ADD THIS

#[Route('/product')]
#[IsGranted('ROLE_STAFF')] // ADD THIS - BOTH admin and staff can access
class ProductController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        
        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setCreatedBy($this->getUser());
            
            $entityManager->persist($product);
            $entityManager->flush();

            $this->activityLogger->log(
                $this->getUser(),
                'CREATE_PRODUCT',
                'Product',
                $product->getId(),
                "Created product: {$product->getName()} (ID: {$product->getId()})"
            );

            $this->addFlash('success', 'Product created successfully.');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        // Staff can only edit their own products
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            if ($product->getCreatedBy()->getId() !== $this->getUser()->getId()) {
                $this->addFlash('error', 'You can only edit your own products.');
                return $this->redirectToRoute('app_product_index');
            }
        }
        
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->activityLogger->log(
                $this->getUser(),
                'UPDATE_PRODUCT',
                'Product',
                $product->getId(),
                "Updated product: {$product->getName()} (ID: {$product->getId()})"
            );

            $this->addFlash('success', 'Product updated successfully.');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')] // ADD THIS - ONLY admin can delete
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        // Remove the manual check since attribute handles it
        // if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
        //     $this->addFlash('error', 'Staff members cannot delete products. Please contact an administrator.');
        //     return $this->redirectToRoute('app_product_index');
        // }
        
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $productName = $product->getName();
            $productId = $product->getId();
            
            $entityManager->remove($product);
            $entityManager->flush();

            $this->activityLogger->log(
                $this->getUser(),
                'DELETE_PRODUCT',
                'Product',
                $productId,
                "Deleted product: {$productName} (ID: {$productId})"
            );

            $this->addFlash('success', 'Product deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}