<?php

namespace App\Security;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function checkPreAuth(UserInterface $user): void
    {
        $this->logger->info('UserChecker::checkPreAuth() called', [
            'user' => $user->getUserIdentifier(),
            'is_user_entity' => $user instanceof User,
        ]);
        
        if (!$user instanceof User) {
            return;
        }

        // Check if user is enabled
        if (!$user->isEnabled()) {
            $this->logger->error('User blocked: account disabled', [
                'user' => $user->getUserIdentifier(),
            ]);
            throw new CustomUserMessageAccountStatusException(
                'Your account has been disabled. Please contact an administrator.'
            );
        }
        
        $this->logger->info('UserChecker::checkPreAuth() passed');
    }

    public function checkPostAuth(UserInterface $user): void
    {
        $this->logger->info('UserChecker::checkPostAuth() called', [
            'user' => $user->getUserIdentifier(),
            'is_verified' => $user instanceof User ? $user->isVerified() : 'N/A',
        ]);
        
        if (!$user instanceof User) {
            return;
        }

        // Check if email is verified
        if (!$user->isVerified()) {
            $this->logger->error('User blocked: email not verified', [
                'user' => $user->getUserIdentifier(),
            ]);
            throw new CustomUserMessageAccountStatusException(
                'Please verify your email address before logging in. ' .
                '<a href="/resend-verification?email=' . $user->getEmail() . '" style="color: #f5e56b; text-decoration: underline;">' .
                'Resend verification email</a>'
            );
        }
        
        $this->logger->info('UserChecker::checkPostAuth() passed');
    }
}