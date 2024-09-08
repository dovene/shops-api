<?php

namespace App\Entity;

use App\Repository\BusinessPartnerRepository;
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
    normalizationContext: ['groups' => ['businesspartner:read']],
    denormalizationContext: ['groups' => ['businesspartner:write']],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ]
)]*/
#[ORM\Entity(repositoryClass: BusinessPartnerRepository::class)]

class BusinessPartner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Serializer\Groups(groups: ['businesspartner:read','event:read'])]
    private ?int $id;

    #[ORM\Column(length: 100, nullable: false)]
    #[Serializer\Groups(groups: ['businesspartner:read','businesspartner:write', 'event:read'])]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['businesspartner:read', 'businesspartner:write', 'event:read'])]
    private ?string $email = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['businesspartner:read', 'businesspartner:write', 'event:read'])]
    private ?string $city = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['company:read', 'company:write', 'event:read'])]
    private ?string $tel = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['businesspartner:read', 'businesspartner:write', 'event:read'])]
    private ?string $country = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['businesspartner:read', 'businesspartner:write', 'event:read'])]
    private ?string $type = null;

    #[ORM\Column(length: 200, nullable: true)]
    #[Groups(['businesspartner:read', 'businesspartner:write', 'event:read'])]
    private ?string $address = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: "company_id", referencedColumnName: "id", nullable: false)]
    #[Serializer\Groups(groups: ['businesspartner:read','businesspartner:write'])]
    private $company;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    #[Serializer\Groups(groups: ['businesspartner:read','businesspartner:write'])]
    private $user;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Serializer\Groups(groups: ['businesspartner:read'])]
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(?string $tel): self
    {
        $this->tel = $tel;

        return $this;
    }

}