<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;

class ApiAuthController extends AbstractController
{
    #[Route('/api/auth/google', name: 'api_google_auth', methods: ['POST'])]
    public function googleLogin(
        Request $request,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            $idToken = $data['idToken'] ?? null;
            
            $logger->info('Google auth request received', ['has_token' => !empty($idToken)]);

            if (!$idToken) {
                return $this->json([
                    'success' => false,
                    'error' => 'No token provided'
                ], 400);
            }

            // For testing - create a mock user if token is "TEST_TOKEN"
            if ($idToken === 'TEST_TOKEN') {
                $logger->info('Using TEST_TOKEN mode');
                $email = 'test@example.com';
                $name = 'Test User';
                $googleId = 'test_google_id_' . time();
                $picture = null;
            } else {
                // Verify Google ID token
                $client = HttpClient::create();
                $response = $client->request('GET', 'https://oauth2.googleapis.com/tokeninfo', [
                    'query' => ['id_token' => $idToken]
                ]);

                if ($response->getStatusCode() !== 200) {
                    return $this->json([
                        'success' => false,
                        'error' => 'Invalid token'
                    ], 401);
                }

                $payload = $response->toArray();
                $logger->info('Google token payload received', ['email' => $payload['email'] ?? 'unknown']);
                
                $email = $payload['email'];
                $name = $payload['name'] ?? $email;
                $googleId = $payload['sub'];
                $picture = $payload['picture'] ?? null;
            }

            // Find or create user
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $logger->info('Creating new user', ['email' => $email]);
                $user = new User();  // Constructor already sets createdAt
                $user->setEmail($email);
                
                // Split name into first and last name
                $nameParts = explode(' ', $name, 2);
                $user->setFirstName($nameParts[0] ?? $name);
                $user->setLastName($nameParts[1] ?? '');
                
                $user->setPassword(''); // No password for Google users
                $user->setIsActive(true);
                $user->setIsVerified(true);
                $user->setGoogleId($googleId);
                $user->setAvatarUrl($picture);
                $user->setRegistrationSource('google');
                $user->setRoles(['ROLE_USER']);
                // DO NOT call setCreatedAt() - it's already set in constructor!
                
                $em->persist($user);
                $em->flush();
                $logger->info('User created successfully', ['user_id' => $user->getId()]);
            } else {
                $logger->info('Existing user found', ['user_id' => $user->getId()]);
                // Update existing user's Google ID if not set
                if (!$user->getGoogleId()) {
                    $user->setGoogleId($googleId);
                }
                if ($picture && !$user->getAvatarUrl()) {
                    $user->setAvatarUrl($picture);
                }
                $em->flush();
                $logger->info('User updated successfully');
            }

            // Generate JWT token for the user
            $jwt = $jwtManager->create($user);
            $logger->info('JWT token generated successfully');

            return $this->json([
                'success' => true,
                'token' => $jwt,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'fullName' => $user->getFullName(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'roles' => $user->getRoles(),
                    'verified' => $user->isVerified(),
                    'avatarUrl' => $user->getAvatarUrl()
                ]
            ]);
        } catch (\Exception $e) {
            $logger->error('Google auth error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->json([
                'success' => false,
                'error' => 'Authentication failed: ' . $e->getMessage()
            ], 401);
        }
    }
}