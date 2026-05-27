<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class GoogleAuthenticator extends OAuth2Authenticator
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private LoggerInterface $logger;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        LoggerInterface $logger
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->logger = $logger;
    }

    public function supports(Request $request): ?bool
    {
        // Only handle the OAuth callback route
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $this->logger->info('Starting Google authentication');
        
        try {
            // Get the Google client
            $client = $this->clientRegistry->getClient('google');
            
            // This will automatically handle the code exchange
            $accessToken = $this->fetchAccessToken($client);
            
            $this->logger->info('Access token obtained', [
                'token' => substr($accessToken->getToken(), 0, 20) . '...'
            ]);
            
            return new SelfValidatingPassport(
                new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                    try {
                        // Fetch user from Google
                        $googleUser = $client->fetchUserFromToken($accessToken);
                        
                        $this->logger->info('Google user fetched', [
                            'email' => $googleUser->getEmail(),
                            'name' => $googleUser->getName()
                        ]);
                        
                        // Find or create user in database
                        $user = $this->entityManager->getRepository(User::class)
                            ->findOneBy(['email' => $googleUser->getEmail()]);
                        
                        if (!$user) {
                            $user = new User();
                            $user->setEmail($googleUser->getEmail());
                            $user->setFirstName($googleUser->getFirstName() ?? '');
                            $user->setLastName($googleUser->getLastName() ?? '');
                            $user->setGoogleId($googleUser->getId());
                            $user->setRoles(['ROLE_USER', 'ROLE_STAFF']);
                            $user->setIsVerified(true);
                            
                            $this->entityManager->persist($user);
                            $this->logger->info('New user created', ['email' => $user->getEmail()]);
                        } else {
                            // Update existing user's Google ID if not set
                            if (!$user->getGoogleId()) {
                                $user->setGoogleId($googleUser->getId());
                            }
                            
                            // Ensure STAFF role
                            $roles = $user->getRoles();
                            if (!in_array('ROLE_STAFF', $roles)) {
                                $roles[] = 'ROLE_STAFF';
                                $user->setRoles($roles);
                            }
                            
                            $user->setIsVerified(true);
                            $this->logger->info('Existing user updated', ['email' => $user->getEmail()]);
                        }
                        
                        $this->entityManager->flush();
                        
                        return $user;
                        
                    } catch (IdentityProviderException $e) {
                        $this->logger->error('Failed to fetch Google user', [
                            'error' => $e->getMessage(),
                            'response' => $e->getResponseBody()
                        ]);
                        throw $e;
                    }
                })
            );
            
        } catch (\Exception $e) {
            $this->logger->error('Authentication exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new AuthenticationException('Google authentication failed: ' . $e->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->logger->info('Google OAuth success');
        
        // Check if this is an API request (from React Native)
        if ($request->headers->get('Accept') === 'application/json' || 
            $request->headers->get('Content-Type') === 'application/json') {
            // Return JSON response for mobile app
            return new Response(json_encode([
                'success' => true,
                'message' => 'Authentication successful',
                'redirect' => '/dashboard'
            ]), Response::HTTP_OK, ['Content-Type' => 'application/json']);
        }
        
        // Web request - redirect to dashboard
        return new RedirectResponse($this->router->generate('app_dashboard_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->error('Google OAuth failed', [
            'message' => $exception->getMessage()
        ]);
        
        // Check if this is an API request (from React Native)
        if ($request->headers->get('Accept') === 'application/json' || 
            $request->headers->get('Content-Type') === 'application/json') {
            // Return JSON error for mobile app
            return new Response(json_encode([
                'success' => false,
                'error' => $exception->getMessage()
            ]), Response::HTTP_UNAUTHORIZED, ['Content-Type' => 'application/json']);
        }
        
        // Web request - redirect to login
        return new RedirectResponse($this->router->generate('app_login'));
    }
}