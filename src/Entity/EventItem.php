<?php

namespace App\Entity;

use App\Repository\EventItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
/*
#[ApiResource(
    normalizationContext: ['groups' => ['eventitem:read']],
    denormalizationContext: ['groups' => ['eventitem:write']],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ]
)]*/

#[ORM\Entity(repositoryClass: EventItemRepository::class)]
#[ORM\Table(name: "event_item")]
class EventItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    #[Serializer\Groups(groups: ['eventitem:read','event:read'])]
    private int $id;

    #[ORM\Column(type: "integer")]
    #[Serializer\Groups(['eventitem:read', 'eventitem:write', 'event:read'])]
    private int $quantity;

    #[ORM\Column(type: "float", nullable: true)]
    #[Serializer\Groups(['eventitem:read', 'eventitem:write', 'event:read'])]
    private ?float $price;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'eventItems')]
    #[Serializer\Groups(['eventitem:read', 'eventitem:write'])]
    #[ORM\JoinColumn(name: "event_id", referencedColumnName: "id",nullable: false)]
    private Event $event;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[Serializer\Groups(['eventitem:read', 'eventitem:write', 'event:read'])]
    #[ORM\JoinColumn(name: "item_id", referencedColumnName: "id",nullable: false)]
    private Item $item;

    // Getters and setters
    public function getId(): int
    {
        return $this->id;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function setItem(Item $item): self
    {
        $this->item = $item;
        return $this;
    }
}
