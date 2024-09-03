<?php

namespace App\Entity;

use App\Repository\AppMinimalVersionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppMinimalVersionRepository::class)]
#[ORM\Table(name: 'app_minimal_version')]
class AppMinimalVersion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $appId = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $appVersion = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $appOs = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $appName = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isMinimalVersionMandatory = false;

    // Getters and setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppId(): ?string
    {
        return $this->appId;
    }

    public function setAppId(?string $appId): self
    {
        $this->appId = $appId;
        return $this;
    }

    public function getAppVersion(): ?string
    {
        return $this->appVersion;
    }

    public function setAppVersion(?string $appVersion): self
    {
        $this->appVersion = $appVersion;
        return $this;
    }

    public function getAppOs(): ?string
    {
        return $this->appOs;
    }

    public function setAppOs(?string $appOs): self
    {
        $this->appOs = $appOs;
        return $this;
    }

    public function getAppName(): ?string
    {
        return $this->appName;
    }

    public function setAppName(?string $appName): self
    {
        $this->appName = $appName;
        return $this;
    }

    public function isMinimalVersionMandatory(): bool
    {
        return $this->isMinimalVersionMandatory;
    }

    public function setIsMinimalVersionMandatory(bool $isMinimalVersionMandatory): self
    {
        $this->isMinimalVersionMandatory = $isMinimalVersionMandatory;
        return $this;
    }
}