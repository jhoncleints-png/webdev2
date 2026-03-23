<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Check if user is enabled
        if (!$user->isEnabled()) {
            throw new CustomUserMessageAccountStatusException(
                'Your account has been disabled. Please contact an administrator.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Check if email is verified
        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
                'Please verify your email address before logging in. ' .
                '<a href="/resend-verification?email=' . $user->getEmail() . '" style="color: #f5e56b; text-decoration: underline;">' .
                'Resend verification email</a>'
            );
        }
    }
}