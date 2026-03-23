<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    #[Route('/delete-all', name: 'app_activity_log_delete_all', methods: ['POST'])]
    public function deleteAll(EntityManagerInterface $entityManager, ActivityLogRepository $activityLogRepository): JsonResponse
    {
        // Only allow ADMIN users
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $logs = $activityLogRepository->findAll();
            foreach ($logs as $log) {
                $entityManager->remove($log);
            }
            $entityManager->flush();

            return $this->json(['success' => true, 'message' => 'All logs deleted successfully']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}