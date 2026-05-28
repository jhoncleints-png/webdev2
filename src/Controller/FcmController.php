<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\FcmService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FcmController extends AbstractController
{
    public function __construct(
        private FcmService $fcmService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/api/fcm/register', name: 'api_fcm_register', methods: ['POST'])]
    public function registerFcmToken(Request $request, UserRepository $userRepository): JsonResponse
    {
        error_log('[FCM REGISTER] Token registration request received');
        try {
            $data = json_decode($request->getContent(), true);
            $fcmToken = $data['token'] ?? null;
            $email = $data['email'] ?? null;
            error_log('[FCM REGISTER] Email: ' . ($email ?: 'not provided'));
            error_log('[FCM REGISTER] Token: ' . ($fcmToken ? substr($fcmToken, 0, 20) . '...' : 'not provided'));

            if (!$fcmToken) {
                error_log('[FCM REGISTER] FCM token is required');
                return $this->json(['error' => 'FCM token is required'], 400);
            }

            if (!$email) {
                error_log('[FCM REGISTER] Email is required');
                return $this->json(['error' => 'Email is required'], 400);
            }

            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                error_log('[FCM REGISTER] User not found for email: ' . $email);
                return $this->json(['error' => 'User not found'], 404);
            }

            $user->setFcmToken($fcmToken);
            $this->entityManager->flush();
            error_log('[FCM REGISTER] Token saved successfully for user: ' . $email);

            return $this->json([
                'success' => true,
                'message' => 'FCM token registered successfully'
            ]);
        } catch (\Exception $e) {
            error_log('[FCM REGISTER] Error: ' . $e->getMessage());
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
