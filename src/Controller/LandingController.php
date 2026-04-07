<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LandingController extends AbstractController
{
    #[Route('/', name: 'app_landing')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Get featured products (limit to 3 products)
        // You can change this to show specific products or categories
        $featuredProducts = $entityManager
            ->getRepository(Product::class)
            ->findBy([], ['id' => 'DESC'], 3); // Gets latest 3 products
        
        // Alternative: Get products from specific category
        // $featuredProducts = $entityManager
        //     ->getRepository(Product::class)
        //     ->createQueryBuilder('p')
        //     ->join('p.category', 'c')
        //     ->where('c.name = :category')
        //     ->setParameter('category', 'Local Craft Beers')
        //     ->setMaxResults(3)
        //     ->getQuery()
        //     ->getResult();
        
        return $this->render('landing/index.html.twig', [
            'products' => $featuredProducts,
        ]);
    }
}