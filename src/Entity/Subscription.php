<?php

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[ORM\Table(name: "subscription")]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "bigint")]
    #[Serializer\Groups(groups: ['subscription:read','company:read'])]
    private ?int $id = null;

    #[ORM\Column(type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    #[Serializer\Groups(groups: ['subscription:read','company:read'])]
    private \DateTimeInterface $debut;

    #[ORM\Column(type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    #[Serializer\Groups(groups: ['subscription:read','company:read'])]
    private \DateTimeInterface $end;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[Serializer\Groups(groups: ['subscription:read','company:read'])]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    #[ORM\Column(length: 50, options: ["default" => "standard"])]
    #[Serializer\Groups(groups: ['subscription:read','company:read'])]
    private string $type;

    #[ORM\Column(length: 50, options: ["default" => "enabled"])]
    #[Serializer\Groups(groups: ['subscription:read','company:read'])]
    private string $status;

    #[ORM\Column(type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    #[Serializer\Groups(groups: ['subscription:read','company:read'])]
    private \DateTimeInterface $createdAt;

    // Getters and setters...
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDebut(): \DateTimeInterface
    {
        return $this->debut;
    }

    public function setDebut(\DateTimeInterface $debut): self
    {
        $this->debut = $debut;
        return $this;
    }

    public function getEnd(): \DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(\DateTimeInterface $end): self
    {
        $this->end = $end;
        return $this;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
