<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ActivityLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function log(
        User $user,
        string $action,
        string $targetEntity,
        ?int $targetId = null,
        ?string $details = null
    ): void {
        $log = new ActivityLog();
        $log->setUser($user);
        
        // Get user's highest role
        $roles = $user->getRoles();
        $primaryRole = in_array('ROLE_ADMIN', $roles) ? 'ADMIN' : 
                      (in_array('ROLE_STAFF', $roles) ? 'STAFF' : 'USER');
        $log->setUserRole($primaryRole);
        
        $log->setAction($action);
        $log->setTargetEntity($targetEntity);
        $log->setTargetId($targetId);
        $log->setDetails($details);
        
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}