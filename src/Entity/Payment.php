<?php
namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payment')]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    #[Serializer\Groups(groups: ['payment:read','event:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Serializer\Groups(groups: ['payment:read', 'payment:write','event:read'])]
    private ?float $amount = null;

    #[ORM\Column(type: 'datetime')]
    #[Serializer\Groups(groups: ['payment:read', 'payment:write','event:read'])]
    private ?\DateTimeInterface $paymentDate = null;

    #[ORM\Column(type: 'datetime')]
    #[Serializer\Groups(groups: ['payment:read', 'payment:write','event:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Serializer\Groups(groups: ['payment:read', 'payment:write'])]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: PaymentType::class)]
    #[Serializer\Groups(groups: ['payment:read', 'payment:write'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?PaymentType $paymentType = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Serializer\Groups(groups: ['payment:read', 'payment:write'])]
    private ?User $user = null;

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(\DateTimeInterface $paymentDate): self
    {
        $this->paymentDate = $paymentDate;
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

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getPaymentType(): ?PaymentType
    {
        return $this->paymentType;
    }

    public function setPaymentType(?PaymentType $paymentType): self
    {
        $this->paymentType = $paymentType;
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
}
