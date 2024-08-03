<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

/*
#[ApiResource(
    normalizationContext: ['groups' => ['event:read']],
    denormalizationContext: ['groups' => ['event:write']],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ]
)]*/
#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'event')]

class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Serializer\Groups(groups: ['event:read'])]
    private ?int $id;

    #[ORM\Column(type: 'datetime',name: 'event_date', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Serializer\Groups(groups: ['event:read'])]
    private ?\DateTimeInterface $eventDate = null;

    #[ORM\Column(type: 'float',  options: ['default' => 0])]
    #[Groups(['event:read', 'event:write'])]
    private ?float $tva = null;

    #[ORM\Column(type: "string", length: 255, options: ["default" => "VALIDATED"])]
    #[Serializer\Groups(groups: ['event:read','event:write'])]
    private string $status;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Serializer\Groups(groups: ['event:read','event:write'])]
    private $totalPrice;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Serializer\Groups(groups: ['event:read','event:write'])]
    private $totalQuantity;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Serializer\Groups(groups: ['event:read','event:write'])]
    private $discount;

    #[ORM\ManyToOne(targetEntity: BusinessPartner::class)]
    #[ORM\JoinColumn(name: "business_partner_id", referencedColumnName: "id", nullable: false)]
    #[Serializer\Groups(groups: ['event:read','event:write'])]
    private $businessPartner;

    #[ORM\ManyToOne(targetEntity: EventType::class)]
    #[ORM\JoinColumn(name: "event_type_id", referencedColumnName: "id", nullable: false)]
    #[Serializer\Groups(groups: ['event:read','event:write'])]
    private $eventType;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: "company_id", referencedColumnName: "id", nullable: false)]
    #[Serializer\Groups(groups: ['event:read','event:write'])]
    private $company;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    #[Serializer\Groups(groups: ['event:read','event:write'])]
    private $user;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Serializer\Groups(groups: ['event:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventItem::class, cascade: ['persist', 'remove'])]
    #[Serializer\Groups(groups: ['event:read'])]
    private $eventItems;
   
    public function __construct()
    {
        $this->eventItems = new ArrayCollection();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->eventDate;
    }

    public function setEventDate(\DateTimeInterface $eventDate): self
    {
        $this->eventDate = $eventDate;

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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }



    public function getTva(): ?float
    {
        return $this->tva;
    }

    public function setTva(?float $tva): self
    {
        $this->tva = $tva;

        return $this;
    }

    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    public function setDiscount(?float $discount): self
    {
        $this->discount = $discount;

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getTotalQuantity(): ?float
    {
        return $this->totalQuantity;
    }

    public function setTotalQuantity(?float $totalQuantity): self
    {
        $this->totalQuantity = $totalQuantity;

        return $this;
    }

    public function getEventType(): ?EventType
    {
        return $this->eventType;
    }

    public function setEventType(?EventType $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getBusinessPartner(): ?BusinessPartner
    {
        return $this->businessPartner;
    }

    public function setBusinessPartner(?BusinessPartner $businessPartner): self
    {
        $this->businessPartner = $businessPartner;

        return $this;
    }


    public function getEventItems(): Collection
    {
        return $this->eventItems;
    }

    public function addEventItem(EventItem $eventItem): self
    {
        if (!$this->eventItems->contains($eventItem)) {
            $this->eventItems[] = $eventItem;
            $eventItem->setEvent($this);
        }

        return $this;
    }

}