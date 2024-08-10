<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation as Serializer;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

/*
#[ApiResource(
    normalizationContext: ['groups' => ['item:read']],
    denormalizationContext: ['groups' => ['item:write']],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ]
)]*/
#[ORM\Entity(repositoryClass: ItemRepository::class)]
#[ORM\Table(name: 'item')]
#[ORM\UniqueConstraint(name: 'unique_name', columns: ['name', 'company_id'])]
#[ORM\UniqueConstraint(name: 'reference_UNIQUE', columns: ['reference', 'company_id'])]

class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Serializer\Groups(groups: ['item:read','event:read'])]
    private ?int $id;

    #[ORM\Column(length: 100, nullable: false)]
    #[Serializer\Groups(groups: ['item:read','item:write','event:read'])]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['item:read', 'item:write','event:read'])]
    #[Assert\NotBlank]
    private ?string $reference = null;

    #[ORM\Column(length: 300, nullable: true)]
    #[Groups(['item:read', 'item:write'])]
    private ?string $picture = null;

    #[ORM\Column(type: 'float', options: ['default' => 0])]
    #[Groups(['item:read', 'item:write'])]
    private ?float $sellPrice = null;

    #[ORM\Column(type: 'float', options: ['default' => 0])]
    #[Groups(['item:read', 'item:write'])]
    private ?float $buyPrice = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['item:read'])]
    private ?int $quantity = null;


    #[ORM\ManyToOne(targetEntity: ItemCategory::class)]
    #[ORM\JoinColumn(name: "item_category_id", referencedColumnName: "id", nullable: false)]
    #[Serializer\Groups(groups: ['item:read','item:write'])]
    private $itemCategory;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: "company_id", referencedColumnName: "id", nullable: false)]
    #[Serializer\Groups(groups: ['item:read','item:write'])]
    private $company;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    #[Serializer\Groups(groups: ['item:read','item:write'])]
    private $user;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Serializer\Groups(groups: ['item:read'])]
    private ?\DateTimeInterface $createdAt = null;

   

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setpicture(?string $picture): self
    {
        $this->picture = $picture;

        return $this;
    }

    public function getBuyPrice(): ?float
    {
        return $this->buyPrice;
    }

    public function setBuyPrice(?float $buyPrice): self
    {
        $this->buyPrice = $buyPrice;

        return $this;
    }


    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getSellPrice(): ?float
    {
        return $this->sellPrice;
    }

    public function setSellPrice(?float $sellPrice): self
    {
        $this->sellPrice = $sellPrice;

        return $this;
    }

    public function getItemCategory(): ?ItemCategory
    {
        return $this->itemCategory;
    }

    public function setItemCategory(?ItemCategory $itemCategory): self
    {
        $this->itemCategory = $itemCategory;

        return $this;
    }
}