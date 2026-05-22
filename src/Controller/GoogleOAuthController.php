<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GoogleOAuthController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        // For web browser OAuth flow
        $client = $clientRegistry->getClient('google');
        
        // Debug: Log the redirect URI being used
        $redirectUri = $this->generateUrl('connect_google_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
        error_log('Google OAuth Redirect URI: ' . $redirectUri);
        error_log('Google OAuth Client ID: ' . $_ENV['GOOGLE_CLIENT_ID'] ?? 'not set');
        
        return $client->redirect([
            'openid', 'email', 'profile'
        ]);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request): Response
    {
        // This route is handled by GoogleAuthenticator
        // If we reach here, something went wrong
        return $this->redirectToRoute('app_login');
    }
}