<?php

namespace App\Controller\Api;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    #[Route('', name: 'api_categories_list', methods: ['GET'])]
    public function getCategories(EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $categories = $em->getRepository(Category::class)->findAll();
        
        $data = $serializer->serialize($categories, 'json', ['groups' => ['category:read']]);
        
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
