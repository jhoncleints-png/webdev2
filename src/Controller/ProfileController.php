<?php

namespace App\Controller;

use App\Form\ChangePasswordFormType;
use App\Form\UserProfileType;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted; // ADD THIS

#[Route('/profile')]
#[IsGranted('ROLE_USER')] // ADD THIS - All logged in users can access
class ProfileController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route('/', name: 'app_profile_index', methods: ['GET'])]
    public function index(): Response
    {
        // Show user's own profile
        $user = $this->getUser();
        
        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $originalEmail = $user->getEmail();
        
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            // Log profile update
            $this->activityLogger->log(
                $user,
                'UPDATE_PROFILE',
                'User',
                $user->getId(),
                "Updated profile: {$originalEmail} -> {$user->getEmail()}"
            );
            
            $this->addFlash('success', 'Your profile has been updated successfully.');
            return $this->redirectToRoute('app_profile_index');
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/change-password', name: 'app_profile_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encode the plain password
            $encodedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($encodedPassword);
            $entityManager->flush();
            
            // Log password change
            $this->activityLogger->log(
                $user,
                'CHANGE_PASSWORD',
                'User',
                $user->getId(),
                "Changed password for user: {$user->getEmail()}"
            );
            
            // Log out the user
            $this->addFlash('success', 'Your password has been changed successfully. Please login again.');
            return $this->redirectToRoute('app_logout');
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}