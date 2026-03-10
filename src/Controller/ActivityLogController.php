<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/activity-logs')]
class ActivityLogController extends AbstractController
{
    #[Route('/', name: 'app_activity_log_index', methods: ['GET'])]
    public function index(ActivityLogRepository $activityLogRepository): Response
    {
        // Only allow ADMIN users
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $logs = $activityLogRepository->findBy([], ['createdAt' => 'DESC']);
        
        return $this->render('activity_log/index.html.twig', [
            'activity_logs' => $logs,
        ]);
    }
}