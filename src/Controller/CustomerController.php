<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/customer')]
#[IsGranted('ROLE_STAFF')] // Both admin and staff can access
final class CustomerController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route(name: 'app_customer_index', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository): Response
    {
        return $this->render('customer/index.html.twig', [
            'customers' => $customerRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_customer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // SET CREATED BY - ADD THIS LINE
            $customer->setCreatedBy($this->getUser());
            
            $entityManager->persist($customer);
            $entityManager->flush();

            // Log CREATE_CUSTOMER action
            $this->activityLogger->log(
                $this->getUser(),
                'CREATE_CUSTOMER',
                'Customer',
                $customer->getId(),
                "Created customer: {$customer->getName()} (ID: {$customer->getId()}, Email: {$customer->getEmail()})"
            );

            $this->addFlash('success', 'Customer created successfully.');
            return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('customer/new.html.twig', [
            'customer' => $customer,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_customer_show', methods: ['GET'])]
    public function show(Customer $customer): Response
    {
        return $this->render('customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_customer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Customer $customer, EntityManagerInterface $entityManager): Response
    {
        // ADD THIS CHECK: Staff can only edit their own customers
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            if ($customer->getCreatedBy()->getId() !== $this->getUser()->getId()) {
                $this->addFlash('error', 'You can only edit your own customers.');
                return $this->redirectToRoute('app_customer_index');
            }
        }
        
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Log UPDATE_CUSTOMER action
            $this->activityLogger->log(
                $this->getUser(),
                'UPDATE_CUSTOMER',
                'Customer',
                $customer->getId(),
                "Updated customer: {$customer->getName()} (ID: {$customer->getId()})"
            );

            $this->addFlash('success', 'Customer updated successfully.');
            return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('customer/edit.html.twig', [
            'customer' => $customer,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_customer_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')] // Only admin can delete customers
    public function delete(Request $request, Customer $customer, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$customer->getId(), $request->getPayload()->getString('_token'))) {
            $customerName = $customer->getName();
            $customerId = $customer->getId();
            $customerEmail = $customer->getEmail();
            
            $entityManager->remove($customer);
            $entityManager->flush();

            // Log DELETE_CUSTOMER action
            $this->activityLogger->log(
                $this->getUser(),
                'DELETE_CUSTOMER',
                'Customer',
                $customerId,
                "Deleted customer: {$customerName} (ID: {$customerId}, Email: {$customerEmail})"
            );

            $this->addFlash('success', 'Customer deleted successfully.');
        }

        return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
    }
}