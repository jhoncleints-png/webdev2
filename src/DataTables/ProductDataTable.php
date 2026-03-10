<?php

namespace App\DataTables;

use App\Entity\Product;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;

class ProductDataTable implements DataTableTypeInterface
{
    public function configure(DataTable $dataTable, array $options): void
    {
        $dataTable
            ->add('id', TextColumn::class, [
                'label' => 'ID',
                'searchable' => true,
                'orderable' => true,
            ])
            ->add('name', TextColumn::class, [
                'label' => 'Product Name',
                'searchable' => true,
                'orderable' => true,
                'render' => function($value, Product $product) {
                    // Make name clickable to view product
                    return sprintf(
                        '<a href="/product/%d" class="text-blue-600 hover:text-blue-800">%s</a>',
                        $product->getId(),
                        htmlspecialchars($value)
                    );
                }
            ])
            ->add('description', TextColumn::class, [
                'label' => 'Description',
                'searchable' => true,
                'orderable' => false,
                'render' => function($value) {
                    // Truncate long descriptions
                    if (strlen($value) > 50) {
                        return htmlspecialchars(substr($value, 0, 50)) . '...';
                    }
                    return htmlspecialchars($value);
                }
            ])
            ->add('price', TextColumn::class, [
                'label' => 'Price',
                'searchable' => true,
                'orderable' => true,
                'render' => function($value) {
                    return '₱' . number_format($value, 2);
                }
            ])
            ->add('category', TextColumn::class, [
                'label' => 'Category',
                'field' => 'c.name', // Use joined category name
                'searchable' => true,
                'orderable' => true,
                'render' => function($value, Product $product) {
                    if ($product->getCategory()) {
                        return htmlspecialchars($product->getCategory()->getName());
                    }
                    return 'No Category';
                }
            ])
            ->add('createdBy', TextColumn::class, [
                'label' => 'Created By',
                'field' => 'u.email', // Use joined user email
                'searchable' => true,
                'orderable' => true,
                'render' => function($value, Product $product) {
                    if ($product->getCreatedBy()) {
                        return htmlspecialchars($product->getCreatedBy()->getEmail());
                    }
                    return 'Unknown';
                }
            ])
            ->add('createdAt', DateTimeColumn::class, [
                'label' => 'Created At',
                'format' => 'Y-m-d H:i',
                'searchable' => false,
                'orderable' => true,
            ])
            ->add('actions', TextColumn::class, [
                'label' => 'Actions',
                'searchable' => false,
                'orderable' => false,
                'render' => function($value, Product $product) {
                    // Action buttons - we'll use Twig's csrf_token() in the template
                    // This is simplified; actual delete form will be in template
                    return sprintf(
                        '<div class="flex space-x-2">
                            <a href="/product/%d/edit" class="text-green-600 hover:text-green-900" title="Edit">
                                Edit
                            </a>
                            <a href="/product/%d" class="text-blue-600 hover:text-blue-900" title="View">
                                View
                            </a>
                        </div>',
                        $product->getId(),
                        $product->getId()
                    );
                }
            ])
            ->addOrderBy('createdAt', 'desc') // Default sort by newest
        ;
    }
}