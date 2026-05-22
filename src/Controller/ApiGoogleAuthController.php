<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;

class ApiGoogleAuthController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/api/google-auth', name: 'api_google_auth', methods: ['POST'])]
    public function googleAuth(
        Request $request,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        // Log the request
        $this->logger->info('Google auth API called');

        // Get the data from React Native
        $data = json_decode($request->getContent(), true);
        
        // Extract user info from the request
        $email = $data['email'] ?? null;
        $name = $data['name'] ?? null;
        $idToken = $data['idToken'] ?? null;
        
        // Log received data (without sensitive info)
        $this->logger->info('Google auth data received', [
            'email' => $email,
            'name' => $name,
            'has_id_token' => !empty($idToken)
        ]);
        
        // Validate required fields
        if (!$email) {
            $this->logger->warning('Google auth failed: No email provided');
            return $this->json([
                'success' => false,
                'error' => 'Email is required'
            ], 400);
        }
        
        if (!$idToken) {
            $this->logger->warning('Google auth failed: No ID token provided for email: ' . $email);
            return $this->json([
                'success' => false,
                'error' => 'ID token is required'
            ], 400);
        }
        
        try {
            // Find or create user
            $user = $entityManager->getRepository(User::class)
                ->findOneBy(['email' => $email]);
            
            if (!$user) {
                // Create new user
                $this->logger->info('Creating new user for email: ' . $email);
                
                $user = new User();
                $user->setEmail($email);
                
                // Split name into first and last
                $nameParts = explode(' ', $name ?? 'Google User', 2);
                $user->setFirstName($nameParts[0]);
                $user->setLastName($nameParts[1] ?? '');
                $user->setRoles(['ROLE_USER', 'ROLE_STAFF']);
                $user->setIsVerified(true);
                
                $entityManager->persist($user);
                $entityManager->flush();
                
                $this->logger->info('New user created', ['user_id' => $user->getId(), 'email' => $email]);
            } else {
                $this->logger->info('Existing user found', ['user_id' => $user->getId(), 'email' => $email]);
                
                // Update existing user's name if needed
                if ($user->getFirstName() === 'Google User' && $name) {
                    $nameParts = explode(' ', $name, 2);
                    $user->setFirstName($nameParts[0]);
                    $user->setLastName($nameParts[1] ?? '');
                    $entityManager->flush();
                    $this->logger->info('Updated user name', ['user_id' => $user->getId()]);
                }
            }
            
            // Generate JWT token for API access
            $jwtToken = $jwtManager->create($user);
            $this->logger->info('JWT token generated for user', ['user_id' => $user->getId()]);
            
            // Return success response
            return $this->json([
                'success' => true,
                'token' => $jwtToken,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'fullName' => $user->getFullName(),
                    'roles' => $user->getRoles(),
                    'isVerified' => $user->isVerified()
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Google auth error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}