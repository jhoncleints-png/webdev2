<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted; // ADD THIS

#[Route('/user')]
#[IsGranted('ROLE_ADMIN')] // ADD THIS - ONLY admin can access user management
final class UserController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            
            $entityManager->persist($user);
            $entityManager->flush();

            // Log CREATE_USER action
            $this->activityLogger->log(
                $this->getUser(),
                'CREATE_USER',
                'User',
                $user->getId(),
                "Created user: {$user->getEmail()} (ID: {$user->getId()}, Role: " . implode(', ', $user->getRoles()) . ", Status: " . ($user->isActive() ? 'Active' : 'Disabled') . ")"
            );

            $this->addFlash('success', 'User created successfully.');
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Optional: Update password if provided
            if ($form->has('plainPassword') && $form->get('plainPassword')->getData()) {
                $plainPassword = $form->get('plainPassword')->getData();
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }
            
            $entityManager->flush();

            // Log UPDATE_USER action
            $this->activityLogger->log(
                $this->getUser(),
                'UPDATE_USER',
                'User',
                $user->getId(),
                "Updated user: {$user->getEmail()} (ID: {$user->getId()}, Status: " . ($user->isActive() ? 'Active' : 'Disabled') . ")"
            );

            $this->addFlash('success', 'User updated successfully.');
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            // Prevent deleting yourself
            if ($this->getUser()->getId() === $user->getId()) {
                $this->addFlash('error', 'You cannot delete your own account.');
            } else {
                $userEmail = $user->getEmail();
                $userId = $user->getId();
                
                $entityManager->remove($user);
                $entityManager->flush();

                // Log DELETE_USER action (required by rubric)
                $this->activityLogger->log(
                    $this->getUser(),
                    'DELETE_USER',
                    'User',
                    $userId, // Use saved ID since user is removed
                    "Deleted user: {$userEmail} (ID: {$userId})"
                );

                $this->addFlash('success', 'User deleted successfully.');
            }
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle-status', name: 'app_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle-status'.$user->getId(), $request->getPayload()->getString('_token'))) {
            // Prevent disabling yourself
            if ($this->getUser()->getId() === $user->getId()) {
                $this->addFlash('error', 'You cannot disable your own account.');
            } else {
                $oldStatus = $user->isActive() ? 'Active' : 'Disabled';
                $user->setIsActive(!$user->isActive());
                $newStatus = $user->isActive() ? 'Active' : 'Disabled';
                
                $entityManager->flush();

                // Log STATUS_CHANGE action
                $this->activityLogger->log(
                    $this->getUser(),
                    'STATUS_CHANGE',
                    'User',
                    $user->getId(),
                    "Changed user status: {$user->getEmail()} (ID: {$user->getId()}) from {$oldStatus} to {$newStatus}"
                );

                $statusText = $user->isActive() ? 'activated' : 'disabled';
                $this->addFlash('success', "User account {$statusText} successfully.");
            }
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}