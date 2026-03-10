<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('firstName', TextType::class, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('lastName', TextType::class, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Admin' => 'ROLE_ADMIN',
                    'Staff' => 'ROLE_STAFF',
                    'User' => 'ROLE_USER',
                ],
                'multiple' => true,
                'expanded' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('isActive', CheckboxType::class, [ // ADDED THIS FIELD
                'label' => 'Account Active',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ]);
        
        // Password field with proper handling for new vs edit
        $builder->add('plainPassword', PasswordType::class, [
            'mapped' => false,
            'required' => $options['is_new'], // Required only for new users
            'label' => $options['is_new'] ? 'Password (required)' : 'New Password (optional)',
            'attr' => [
                'autocomplete' => 'new-password', 
                'class' => 'form-control',
                'placeholder' => $options['is_new'] ? 'Enter password' : 'Leave blank to keep current'
            ],
            'constraints' => $options['is_new'] ? [
                new NotBlank([
                    'message' => 'Please enter a password',
                ]),
                new Length([
                    'min' => 6,
                    'minMessage' => 'Your password should be at least {{ limit }} characters',
                    'max' => 4096,
                ]),
            ] : [],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_new' => false,
        ]);
    }
}