<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CustomerResolver
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function resolveForUser(User $user): Customer
    {
        $repository = $this->entityManager->getRepository(Customer::class);

        $customer = $repository->findOneBy([
            'email' => $user->getEmail(),
            'createdBy' => $user,
        ]);

        if (!$customer) {
            $customer = $repository->findOneBy(['email' => $user->getEmail()]);
        }

        if ($customer) {
            return $customer;
        }

        $name = trim($user->getFullName());
        if ($name === '' || strlen($name) < 2) {
            $name = strstr($user->getEmail(), '@', true) ?: 'Customer';
        }

        $customer = new Customer();
        $customer->setEmail($user->getEmail());
        $customer->setName($name);
        $customer->setCreatedBy($user);

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $customer;
    }
}
