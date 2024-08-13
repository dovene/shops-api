<?php

namespace App\Controller;

use App\Entity\PaymentType;
use App\Repository\PaymentTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/paymenttypes')]
class PaymentTypeController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private PaymentTypeRepository $paymentTypeRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaymentTypeRepository $paymentTypeRepository
    ) {
        $this->entityManager = $entityManager;
        $this->paymentTypeRepository = $paymentTypeRepository;
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $paymentTypes = $this->paymentTypeRepository->findAll();
        return $this->json($paymentTypes);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $paymentType = $this->paymentTypeRepository->find($id);
        if (!$paymentType) {
            return $this->json(['message' => 'Payment Type not found'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($paymentType);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'])) {
            return $this->json(['message' => 'Missing required field name '], JsonResponse::HTTP_BAD_REQUEST);
        }

        $existingInstance = $this->paymentTypeRepository->findOneBy(['name' => $data['name']]);
        if ($existingInstance) {
            return $this->json(['message' => 'Payment type with this name already exists'], JsonResponse::HTTP_CONFLICT);
        }

        $instance = new PaymentType();
        $instance->setName($data['name']);
        $instance->setCreatedAt(new \DateTime());

        $this->entityManager->persist($instance);
        $this->entityManager->flush();

        return $this->json($instance, JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $instance = $this->paymentTypeRepository->find($id);

        if (!$instance) {
            return $this->json(['message' => 'Payment Type not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $instance->setName($data['name'] ?? $instance->getName());

        $this->entityManager->flush();

        return $this->json($instance);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $instance = $this->paymentTypeRepository->find($id);

        if (!$instance) {
            return $this->json(['message' => 'Payment Type not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($instance);
        $this->entityManager->flush();

        return $this->json(['message' => 'Payment Type deleted successfully']);
    }
}
