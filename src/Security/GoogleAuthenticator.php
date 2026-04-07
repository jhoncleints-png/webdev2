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
        $route = $request->attributes->get('_route');
        $this->logger->info('GoogleAuthenticator::supports() called', [
            'route' => $route,
            'expected' => 'connect_google_check',
            'matches' => $route === 'connect_google_check',
            'path' => $request->getPathInfo(),
            'query' => $request->query->all(),
        ]);
        
        // Support both GET and POST for OAuth callback
        if ($route !== 'connect_google_check') {
            return false;
        }
        
        // Check for OAuth parameters
        $hasCode = $request->query->has('code') || $request->request->has('code');
        $hasState = $request->query->has('state') || $request->request->has('state');
        
        $this->logger->info('OAuth params check', ['has_code' => $hasCode, 'has_state' => $hasState]);
        
        return $hasCode; // Need authorization code to proceed
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                $googleUser = $client->fetchUserFromToken($accessToken);
                
                $user = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $googleUser->getEmail()]);
                
                if (!$user) {
                    $user = new User();
                    $user->setEmail($googleUser->getEmail());
                    $user->setFirstName($googleUser->getFirstName() ?? 'Google');
                    $user->setLastName($googleUser->getLastName() ?? 'User');
                    $user->setGoogleId($googleUser->getId());
                    // Assign STAFF role for Google login users
                    $user->setRoles(['ROLE_USER', 'ROLE_STAFF']);
                    // Google emails are pre-verified
                    $user->setIsVerified(true);
                    
                    $this->entityManager->persist($user);
                } else {
                    // If user exists but doesn't have STAFF role, add it
                    $roles = $user->getRoles();
                    if (!in_array('ROLE_STAFF', $roles)) {
                        $roles[] = 'ROLE_STAFF';
                        $user->setRoles($roles);
                    }
                    // Ensure Google users are marked as verified
                    if (!$user->isVerified()) {
                        $user->setIsVerified(true);
                    }
                }
                
                $this->entityManager->flush();
                
                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->logger->info('Google OAuth success - redirecting to dashboard', [
            'user' => $token->getUserIdentifier(),
            'roles' => $token->getRoleNames(),
        ]);
        
        // Create redirect response
        $response = new RedirectResponse('/dashboard');
        
        $this->logger->info('Redirect response created', [
            'target' => '/dashboard',
            'status' => $response->getStatusCode(),
        ]);
        
        return $response;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->error('Google OAuth authentication failed', [
            'message' => $exception->getMessage(),
            'path' => $request->getPathInfo(),
        ]);
        
        return new RedirectResponse($this->router->generate('app_login'));
    }
}