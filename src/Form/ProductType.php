<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'constraints' => [
                    new NotBlank(['message' => 'Product name is required']),
                ],
                'attr' => [
                    'placeholder' => 'Enter product name',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Enter product description',
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'scale' => 2,
                'constraints' => [
                    new NotBlank(['message' => 'Price is required']),
                    new Positive(['message' => 'Price must be greater than 0']),
                ],
                'attr' => [
                    'placeholder' => '0.00',
                    'step' => '0.01',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Category',
                'placeholder' => 'Choose a category',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please select a category']),
                ],
            ])
            ->add('minimumStock', NumberType::class, [
                'label' => 'Minimum Stock Alert',
                'required' => false,
                'constraints' => [
                    new PositiveOrZero(['message' => 'Minimum stock must be zero or positive']),
                ],
                'attr' => [
                    'placeholder' => 'e.g., 10',
                ],
                'help' => 'Set a minimum stock level to receive low stock alerts',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}