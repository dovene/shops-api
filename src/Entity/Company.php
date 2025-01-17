<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
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
    normalizationContext: ['groups' => ['company:read']],
    denormalizationContext: ['groups' => ['company:write']],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ]
)]*/
#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[UniqueEntity(fields: ['email'],
message: 'Cet email est déjà utilisé')]
#[UniqueEntity(fields: ['name'],
message: 'Cet nom de société est déjà utilisé')]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['company:read', 'user:read' ,'item:read', 'itemcategory:read', 'businesspartner:read', 'event:read', 'eventtype:read', 'subscription:read'])]
    private ?int $id;

    #[ORM\Column(length: 100, unique: true)]
    #[Groups(['company:read', 'company:write', 'user:read','item:read', 'itemcategory:read', 'businesspartner:read', 'event:read', 'eventtype:read', 'subscription:read'])]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['company:read', 'company:write'])]
    private ?string $tel = null;

    #[ORM\Column(length: 45)]
    #[Groups(['company:read', 'company:write'])]
    #[Assert\NotBlank]
    #[Assert\Email(message: 'Cet email n\'est pas valide')]
    private ?string $email = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['company:read', 'company:write'])]
    private ?string $city = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['company:read', 'company:write'])]
    private ?string $country = null;

    #[ORM\Column(length: 200, nullable: true)]
    #[Groups(['company:read', 'company:write'])]
    private ?string $addressDetails = null;

    #[ORM\Column(length: 45, options: ['default' => 'draft'])]
    #[Groups(['company:read', 'company:write'])]
    private ?string $status = 'draft';

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups(['company:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['company:read', 'company:write'])]
    private ?string $code = null;

    #[ORM\Column(length: 25, nullable: true)]
    #[Groups(['company:read', 'company:write'])]
    private ?string $currency = null;

    #[ORM\Column(type: 'boolean', name: 'can_default_users_create_items', options: ['default' => 1])]
    #[Groups(['company:read', 'company:write'])]
    private ?int $canDefaultUsersCreateItems = 1;


    #[ORM\Column(type: 'boolean', name: 'can_default_users_cancel_events', options: ['default' => 1])]
    #[Groups(['company:read', 'company:write'])]
    private ?int $canDefaultUsersCancelEvents = 1;
   
    #[ORM\Column(type: 'string', length: 1200, nullable: true)]
    #[Groups(['company:read', 'company:write'])]
    private ?string $termsAndConditions = null;

    #[ORM\Column(type: 'boolean', name: 'should_display_terms', options: ['default' => 1])]
    #[Groups(['company:read', 'company:write'])]
    private ?int $shouldDisplayTerms = 1;
    
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

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(?string $tel): self
    {
        $this->tel = $tel;

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

    public function getAddressDetails(): ?string
    {
        return $this->addressDetails;
    }

    public function setAddressDetails(?string $addressDetails): self
    {
        $this->addressDetails = $addressDetails;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCanDefaultUsersCreateItems(): ?int
    {
        return $this->canDefaultUsersCreateItems;
    }
    
    public function setCanDefaultUsersCreateItems(?int $canDefaultUsersCreateItems): self
    {
        $this->canDefaultUsersCreateItems = $canDefaultUsersCreateItems;

        return $this;
    }

    public function getCanDefaultUsersCancelEvents(): ?int
    {
        return $this->canDefaultUsersCancelEvents;
    }

    public function setCanDefaultUsersCancelEvents(?int $canDefaultUsersCancelEvents): self
    {
        $this->canDefaultUsersCancelEvents = $canDefaultUsersCancelEvents;

        return $this;
    }

    public function getTermsAndConditions(): ?string
    {
        return $this->termsAndConditions;
    }

    public function setTermsAndConditions(?string $termsAndConditions): self
    {
        $this->termsAndConditions = $termsAndConditions;
        return $this;
    }

    public function getShouldDisplayTerms(): ?int
    {
        return $this->shouldDisplayTerms;
    }

    public function setShouldDisplayTerms(?int $shouldDisplayTerms): self
    {
        $this->shouldDisplayTerms = $shouldDisplayTerms;
        return $this;
    }
}