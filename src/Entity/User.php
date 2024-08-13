<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
/*
#[ApiResource(
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ]
)]*/
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Serializer\Groups(groups: ['user:read', 'company:read','item:read', 'itemcategory:read', 'businesspartner:read', 'event:read', 'eventtype:read', 'payment:read'])]
    private $id;

    #[ORM\Column(type: 'string', length: 45, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email(message: 'Cet email n\'est pas valide')]
    #[Serializer\Groups(groups: ['user:read','user:write', 'event:read','item:read', 'itemcategory:read', 'businesspartner:read', 'event:read', 'eventtype:read', 'payment:read'])]
    private $email;

    #[ORM\Column(type: 'string', length: 255)]
    #[Serializer\Groups(groups: ['user:read','user:write', 'event:read','item:read', 'itemcategory:read'])]
    private $name;

    #[ORM\Column(type: 'string', length: 100)]
    #[Serializer\Groups(groups: ['user:read','user:write'])]
    private $password;

    #[ORM\Column(type: 'string', length: 45, options: ["default" => "user"])]
    #[Serializer\Groups(groups: ['user:read','user:write'])]
    private $role = 'user';

    #[ORM\Column(type: 'string', length: 45, options: ["default" => "enabled"])]
    #[Serializer\Groups(groups: ['user:read','user:write'])]
    private $status = 'enabled';

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: "company_id", referencedColumnName: "id", nullable: false)]
    #[Serializer\Groups(groups: ['user:read','user:write'])]
    private $company;

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

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

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    // Implementation of UserInterface methods
    public function getRoles(): array
    {
        return [$this->role];
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
