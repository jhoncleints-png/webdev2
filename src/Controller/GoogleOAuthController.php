<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GoogleOAuthController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        $this->logger->info('Google OAuth: Starting connection flow');
        
        // Redirect to Google
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'openid', 'email', 'profile'
            ]);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request): Response
    {
        $this->logger->error('Google OAuth: connectCheckAction was reached - authenticator did not handle request!', [
            'route' => $request->attributes->get('_route'),
            'path' => $request->getPathInfo(),
            'query' => $request->query->all(),
            'method' => $request->getMethod(),
        ]);
        
        // This route should be handled by GoogleAuthenticator
        // If we reach here, the authenticator didn't intercept the request
        throw new \LogicException('This code should never be reached - the authenticator should handle it. Check security.yaml firewall configuration.');
    }
}
