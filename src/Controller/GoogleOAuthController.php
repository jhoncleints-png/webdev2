<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleOAuthController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        // For web browser OAuth flow
        return $clientRegistry
            ->getClient('google')
            ->redirect([
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