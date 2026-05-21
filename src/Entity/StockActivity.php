<?php

namespace App\Entity;

use App\Repository\StockActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockActivityRepository::class)]
class StockActivity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'stockActivities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $performedBy = null;

    #[ORM\Column]
    private ?int $quantityChange = null;

    #[ORM\Column]
    private ?int $previousQuantity = null;

    #[ORM\Column]
    private ?int $newQuantity = null;

    #[ORM\Column(length: 50)]
    private ?string $actionType = null; // 'added', 'removed', 'adjusted'

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPerformedBy(): ?User
    {
        return $this->performedBy;
    }

    public function setPerformedBy(?User $performedBy): static
    {
        $this->performedBy = $performedBy;
        return $this;
    }

    public function getQuantityChange(): ?int
    {
        return $this->quantityChange;
    }

    public function setQuantityChange(int $quantityChange): static
    {
        $this->quantityChange = $quantityChange;
        return $this;
    }

    public function getPreviousQuantity(): ?int
    {
        return $this->previousQuantity;
    }

    public function setPreviousQuantity(int $previousQuantity): static
    {
        $this->previousQuantity = $previousQuantity;
        return $this;
    }

    public function getNewQuantity(): ?int
    {
        return $this->newQuantity;
    }

    public function setNewQuantity(int $newQuantity): static
    {
        $this->newQuantity = $newQuantity;
        return $this;
    }

    public function getActionType(): ?string
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType): static
    {
        $this->actionType = $actionType;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
