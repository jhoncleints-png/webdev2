<?php

namespace App\Form;

use App\Entity\OrderItem;
use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class OrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => function(Product $product) {
                    return $product->getName() . ' - $' . number_format($product->getPrice(), 2);
                },
                'choice_attr' => function(Product $product) {
                    return ['data-price' => $product->getPrice()];
                },
                'placeholder' => 'Select a product',
                'required' => true,
                'attr' => [
                    'class' => 'product-select form-control',
                ]
            ])
            ->add('quantity', IntegerType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'quantity-input form-control',
                    'min' => 1,
                    'value' => 1
                ]
            ])
            ->add('unitPrice', NumberType::class, [
                'required' => true,
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'class' => 'unit-price-input form-control',
                    'readonly' => true,
                    'step' => '0.01'
                ]
            ]);

        // Auto-fill unitPrice when product is selected
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $orderItem = $event->getData();
            $form = $event->getForm();

            if ($orderItem && $orderItem->getProduct()) {
                $form->get('unitPrice')->setData($orderItem->getProduct()->getPrice());
            }
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $orderItem = $event->getData();
            
            if ($orderItem && $orderItem->getProduct() && !$orderItem->getUnitPrice()) {
                $orderItem->setUnitPrice((string)$orderItem->getProduct()->getPrice());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderItem::class,
        ]);
    }
}