<?php

namespace App\Entity;

use App\Repository\PaymentTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;

#[ORM\Entity(repositoryClass: PaymentTypeRepository::class)]
#[ORM\Table(name: 'payment_type')]
class PaymentType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    #[Serializer\Groups(groups: ['paymenttype:read','payment:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 45, unique: true)]
    #[Serializer\Groups(groups: ['paymenttype:read', 'paymenttype:write','payment:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'datetime')]
    #[Serializer\Groups(groups: ['paymenttype:read', 'paymenttype:write','payment:read'])]
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
}
