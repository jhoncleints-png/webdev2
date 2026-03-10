<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Repository\CategoryRepository;
use App\Repository\CustomerRepository;
use App\Repository\ActivityLogRepository; // ADD THIS
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard_home')]
    public function index(
        ProductRepository $productRepository,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        CustomerRepository $customerRepository,
        ActivityLogRepository $activityLogRepository
    ): Response {
        // Add staff count and total records
        $totalUsers = $userRepository->count([]);
        $totalStaff = $userRepository->countStaff(); 
        
        $totalProducts = $productRepository->count([]);
        $totalCategories = $categoryRepository->count([]);
        $totalCustomers = $customerRepository->count([]);
        $totalRecords = $totalProducts + $totalCategories + $totalCustomers;
        
        // Get recent activities
        $recentActivities = $activityLogRepository->findRecent(10);

        return $this->render('dashboard/index.html.twig', [
            'product_count' => $totalProducts,
            'user_count' => $totalUsers,
            'category_count' => $totalCategories,
            'customer_count' => $totalCustomers,
            'staff_count' => $totalStaff, 
            'total_records' => $totalRecords, 
            'recent_activities' => $recentActivities,
        ]);
    }
}