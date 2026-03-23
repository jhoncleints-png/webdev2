<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailVerificationController extends AbstractController
{
    #[Route('/verify-email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        EmailVerificationService $emailVerificationService
    ): Response {
        $token = $request->query->get('token');

        if (!$token) {
            $this->addFlash('error', 'Verification token is missing.');
            return $this->redirectToRoute('app_register');
        }

        $user = $emailVerificationService->verifyToken($token);

        if (!$user) {
            $this->addFlash('error', 'Invalid or expired verification token. Please register again.');
            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your email has been verified! You can now log in.');

        return $this->redirectToRoute('app_login');
    }
    
    #[Route('/resend-verification', name: 'app_resend_verification')]
    public function resendVerification(
        Request $request,
        EmailVerificationService $emailVerificationService,
        EntityManagerInterface $entityManager
    ): Response {
        $email = $request->query->get('email');
        
        if (!$email) {
            $this->addFlash('error', 'Email address is required.');
            return $this->redirectToRoute('app_login');
        }
        
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_login');
        }
        
        if ($user->isVerified()) {
            $this->addFlash('info', 'This email is already verified. You can log in.');
            return $this->redirectToRoute('app_login');
        }
        
        // Generate new token
        $newToken = $emailVerificationService->generateVerificationToken();
        $user->setVerificationToken($newToken);
        $entityManager->flush();
        
        // Generate verification URL
        $verificationUrl = $this->generateUrl(
            'app_verify_email',
            ['token' => $newToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        // Send verification email
        try {
            $emailVerificationService->sendVerificationEmail($user, $verificationUrl);
            $this->addFlash('success', 'Verification email has been resent. Please check your inbox.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send verification email. Please try again later.');
        }
        
        return $this->redirectToRoute('app_login');
    }
}