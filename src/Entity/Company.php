<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

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
    #[Groups(['company:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Groups(['company:read', 'company:write'])]
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
}