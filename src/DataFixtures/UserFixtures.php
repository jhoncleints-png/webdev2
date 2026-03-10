<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}
    
    public function load(ObjectManager $manager): void
    {
        // 1. ADMIN USER
        $admin = new User();
        $admin->setEmail('admin@gmail.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_STAFF']);
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'admin123')
        );
        $admin->setIsActive(true); // EXPLICITLY SET
        $manager->persist($admin);
        
        // 2. STAFF USER
        $staff = new User();
        $staff->setEmail('staff@gmail.com');
        $staff->setFirstName('Staff');
        $staff->setLastName('User');
        $staff->setRoles(['ROLE_STAFF']);
        $staff->setPassword(
            $this->passwordHasher->hashPassword($staff, 'staff123')
        );
        $staff->setIsActive(true); // EXPLICITLY SET
        $manager->persist($staff);
        
        // 3. REGULAR USER
        $user = new User();
        $user->setEmail('jhon@gmail.com');
        $user->setFirstName('Regular');
        $user->setLastName('User');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'user123')
        );
        $user->setIsActive(true); // EXPLICITLY SET
        $manager->persist($user);
        
        $manager->flush();
    }
}