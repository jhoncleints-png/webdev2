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

        // Check if user is enabled (using your isEnabled() method)
        if (!$user->isEnabled()) {
            throw new CustomUserMessageAccountStatusException(
                'Your account has been disabled. Please contact an administrator.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // You can add post-authentication checks here if needed
    }
}