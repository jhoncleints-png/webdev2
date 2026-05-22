<?php

namespace App\Controller\Api;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    #[Route('', name: 'api_products_list', methods: ['GET'])]
    public function getProducts(EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $products = $em->getRepository(Product::class)->findAll();
        
        $data = $serializer->serialize($products, 'json', ['groups' => ['product:read']]);
        
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'api_product_show', methods: ['GET'])]
    public function getProduct(int $id, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $product = $em->getRepository(Product::class)->find($id);
        
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }
        
        $data = $serializer->serialize($product, 'json', ['groups' => ['product:read']]);
        
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}