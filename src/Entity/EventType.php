<?php

namespace App\Entity;

use App\Repository\EventTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
/*
#[ApiResource(
    normalizationContext: ['groups' => ['eventtype:read']],
    denormalizationContext: ['groups' => ['eventtype:write']],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ]
)]*/
#[ORM\Entity(repositoryClass: EventTypeRepository::class)]
#[ORM\Table(name: 'event_type')]
#[UniqueEntity(fields: ['name'],
message: 'Cet nom de type d\'event est déjà utilisé')]
class EventType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['eventtype:read','event:read'])]
    private ?int $id;

    #[ORM\Column(length: 100, unique: true)]
    #[Groups(['eventtype:read', 'eventtype:write', 'event:read'])]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['eventtype:read', 'eventtype:write', 'event:read'])]
    private ?int $isFree = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['eventtype:read', 'eventtype:write', 'event:read'])]
    private ?int $isAnIncreaseStockType = null;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups(['eventtype:read'])]
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

    public function getIsFree(): ?int
    {
        return $this->isFree;
    }

    public function setIsFree(?int $isFree): self
    {
        $this->isFree = $isFree;

        return $this;
    }

    public function getIsAnIncreaseStockType(): ?int
    {
        return $this->isAnIncreaseStockType;
    }

    public function setIsAnIncreaseStockType(?int $isAnIncreaseStockType): self
    {
        $this->isAnIncreaseStockType = $isAnIncreaseStockType;

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