<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use App\Util\DecimalMath;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Customer is required.")]
    private ?Customer $customer = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Order date is required.")]
    private ?\DateTime $orderDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: "Total amount is required.")]
    #[Assert\Positive(message: "Total amount must be greater than 0.")]
    private ?string $totalAmount = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Status is required.")]
    #[Assert\Choice(choices: [self::STATUS_PENDING, self::STATUS_DELIVERED, self::STATUS_CANCELLED], message: "Invalid status.")]
    private ?string $status = null;

    #[ORM\Column(length: 50)]
    private ?string $orderNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'orderRelation', cascade: ['persist', 'remove'])]
    private Collection $orderItems;

    // ADD THIS: createdBy property
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public static function getStatusChoices(): array
    {
        return [
            'Pending' => self::STATUS_PENDING,
            'Delivered' => self::STATUS_DELIVERED,
            'Cancelled' => self::STATUS_CANCELLED,
        ];
    }

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->orderDate = new \DateTime();
        $this->status = self::STATUS_PENDING;
        $this->orderNumber = 'ORD-' . strtoupper(uniqid());
        $this->totalAmount = '0.00';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getOrderDate(): ?\DateTime
    {
        return $this->orderDate;
    }

    public function setOrderDate(\DateTime $orderDate): static
    {
        $this->orderDate = $orderDate;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrderRelation($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getOrderRelation() === $this) {
                $orderItem->setOrderRelation(null);
            }
        }

        return $this;
    }

    // ADD THESE METHODS: createdBy getter and setter
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getStatusLabel(): string
    {
        $statusLabels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
        
        return $statusLabels[$this->status] ?? $this->status;
    }

    public function calculateTotal(): string
    {
        $total = '0.00';
        foreach ($this->orderItems as $orderItem) {
            $itemTotal = DecimalMath::mul($orderItem->getUnitPrice(), (string) $orderItem->getQuantity(), 2);
            $total = DecimalMath::add($total, $itemTotal, 2);
        }
        return $total;
    }

    public function __toString(): string
    {
        return $this->orderNumber . ' - ' . $this->customer?->getName() . ' (' . $this->getStatusLabel() . ')';
    }
}