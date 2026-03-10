<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\OrderItemType;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a customer',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('orderItems', CollectionType::class, [
                'entry_type' => OrderItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'prototype' => true,
                'required' => false,
                'attr' => [
                    'class' => 'order-items-collection',
                ],
                // These are important for dynamic forms
                'allow_extra_fields' => true,
                'delete_empty' => true,
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Order::getStatusChoices(),
                'placeholder' => 'Select status',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            // Allow extra fields for JavaScript
            'allow_extra_fields' => true,
        ]);
    }
}