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

class ApiAuthController extends AbstractController
{
    #[Route('/api/auth/google', name: 'api_google_auth', methods: ['POST'])]
    public function googleLogin(
        Request $request,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $idToken = $data['idToken'] ?? null;

        if (!$idToken) {
            return $this->json([
                'success' => false,
                'error' => 'No token provided'
            ], 400);
        }

        // Verify Google ID token using Google's tokeninfo endpoint
        $client = HttpClient::create();
        try {
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
            
            // Verify the token is for your app
            $googleClientId = $_ENV['GOOGLE_CLIENT_ID'] ?? null;
            if ($googleClientId && ($payload['aud'] !== $googleClientId)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Token audience mismatch'
                ], 401);
            }

            $email = $payload['email'];
            $name = $payload['name'] ?? $email;
            $googleId = $payload['sub'];
            $picture = $payload['picture'] ?? null;

            // Find or create user
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                
                // Split name into first and last name
                $nameParts = explode(' ', $name, 2);
                $user->setFirstName($nameParts[0] ?? $name);
                $user->setLastName($nameParts[1] ?? '');
                
                $user->setPassword(''); // No password for Google users
                $user->setIsActive(true);
                $user->setIsVerified(true);
                $user->setGoogleId($googleId);
                $user->setRoles(['ROLE_USER']);
                $user->setCreatedAt(new \DateTimeImmutable());
                
                $em->persist($user);
                $em->flush();
            } else {
                // Update existing user's Google ID if not set
                if (!$user->getGoogleId()) {
                    $user->setGoogleId($googleId);
                    $em->flush();
                }
            }

            // Generate JWT token for the user
            $jwt = $jwtManager->create($user);

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
                    'verified' => $user->isVerified()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Token verification failed: ' . $e->getMessage()
            ], 401);
        }
    }
}