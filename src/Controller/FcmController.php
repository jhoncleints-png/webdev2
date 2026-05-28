<?php

namespace App\Controller;

use App\Service\FcmService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class FcmController extends AbstractController
{
    public function __construct(
        private FcmService $fcmService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/fcm-token', name: 'api_fcm_register', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function registerFcmToken(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $fcmToken = $data['fcmToken'] ?? null;

            if (!$fcmToken) {
                return $this->json(['error' => 'FCM token is required'], 400);
            }

            $user = $this->getUser();
            $user->setFcmToken($fcmToken);

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'FCM token registered successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/fcm/test', name: 'api_fcm_test', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function testFcmNotification(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            $fcmToken = $user->getFcmToken();

            if (!$fcmToken) {
                return $this->json([
                    'success' => false,
                    'error' => 'No FCM token registered for this user'
                ], 400);
            }

            $data = json_decode($request->getContent(), true);
            $title = $data['title'] ?? 'Test Notification';
            $body = $data['body'] ?? 'This is a test notification from Samaco Brewery';

            $result = $this->fcmService->sendNotificationLegacy(
                $fcmToken,
                $title,
                $body,
                ['type' => 'test']
            );

            return $this->json([
                'success' => $result,
                'message' => $result ? 'Test notification sent successfully' : 'Failed to send notification'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
