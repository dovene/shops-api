<?php

// src/Controller/EventTypeController.php

namespace App\Controller;

use App\Entity\EventType;
use App\Repository\EventTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/eventtypes')]
class EventTypeController extends AbstractController
{
    private $entityManager;
    private $eventTypeRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventTypeRepository $eventTypeRepository
    ) {
        $this->entityManager = $entityManager;
        $this->eventTypeRepository = $eventTypeRepository;
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $EventTypes = $this->eventTypeRepository->findAll();
        return $this->json($EventTypes);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $EventType = $this->eventTypeRepository->find($id);

        if (!$EventType) {
            return $this->json(['message' => 'Event Type not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json($EventType);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'])) {
            return $this->json(['message' => 'Missing required field name '], JsonResponse::HTTP_BAD_REQUEST);
        }

      

        $existingInstance = $this->eventTypeRepository->findOneBy(['name' => $data['name']]);
        if ($existingInstance) {
            return $this->json(['message' => 'Event type with this name already exists'], JsonResponse::HTTP_CONFLICT);
        }

        $instance = new EventType();
        $instance->setName($data['name']);
        $instance->setIsAnIncreaseStockType($data['is_an_incease_stock_type']?? 0);
        $instance->setIsFree($data['is_free'] ?? 0);
        $instance->setCreatedAt(new \DateTime());

        $this->entityManager->persist($instance);
        $this->entityManager->flush();

        return $this->json($instance, JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $instance = $this->eventTypeRepository->find($id);

        if (!$instance) {
            return $this->json(['message' => 'EventType not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $instance->setName($data['name'] ?? $instance->getName());
        $instance->setIsAnIncreaseStockType($data['is_an_incease_stock_type'] ?? $instance->getIsAnIncreaseStockType());
        $instance->setIsFree($data['is_free'] ?? $instance->getIsFree());

        $this->entityManager->flush();

        return $this->json($instance);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $instance = $this->eventTypeRepository->find($id);

        if (!$instance) {
            return $this->json(['message' => 'Event Type not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($instance);
        $this->entityManager->flush();

        return $this->json(['message' => 'Event Type deleted successfully']);
    }

    #[Route('/name/{name}', methods: ['GET'])]
    public function getByName(string $name): JsonResponse
    {
        $eventType = $this->eventTypeRepository->findOneBy(['name' => $name]);

        if (!$eventType) {
            return $this->json(['message' => 'Event Type not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json($eventType);
    }
    
}
