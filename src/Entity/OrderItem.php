<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Order is required.")]
    private ?Order $orderRelation = null;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Product is required.")]
    private ?Product $product = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Quantity is required.")]
    #[Assert\Positive(message: "Quantity must be greater than 0.")]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: "Unit price is required.")]
    #[Assert\Positive(message: "Unit price must be greater than 0.")]
    private ?string $unitPrice = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderRelation(): ?Order
    {
        return $this->orderRelation;
    }

    public function setOrderRelation(?Order $orderRelation): static
    {
        $this->orderRelation = $orderRelation;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    // ADD THIS METHOD:
    public function getItemTotal(): string
    {
        return \bcmul($this->unitPrice, (string) $this->quantity, 2);
    }

    // Also add a __toString method:
    public function __toString(): string
    {
        return $this->product?->getName() . ' x' . $this->quantity . ' = $' . $this->getItemTotal();
    }
}