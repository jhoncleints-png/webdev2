<?php

namespace App\EventSubscriber;

use App\Service\ActivityLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()->getUser();
        if ($user instanceof \App\Entity\User) {
            $this->activityLogger->log(
                $user, 
                'LOGOUT', 
                'User', 
                $user->getId(), 
                'User logged out'
            );
        }
    }
}