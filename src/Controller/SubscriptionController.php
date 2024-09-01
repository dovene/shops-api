<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\CompanyRepository;

#[Route('/api/subscriptions')]
class SubscriptionController extends AbstractController
{
    private $entityManager;
    private $subscriptionRepository;
    private CompanyRepository $companyRepository;
    private $serializer;

    public function __construct(
        EntityManagerInterface $entityManager,
        SubscriptionRepository $subscriptionRepository,
        CompanyRepository $companyRepository,
        SerializerInterface $serializer
    ) {
        $this->entityManager = $entityManager;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->companyRepository = $companyRepository;
        $this->serializer = $serializer;
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $subscriptions = $this->subscriptionRepository->findAll();
        $data = $this->serializer->serialize($subscriptions, 'json', ['groups' => 'subscription:read']);
        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->find($id);
        if (!$subscription) {
            return $this->json(['message' => 'Subscription not found'], Response::HTTP_NOT_FOUND);
        }
        $data = $this->serializer->serialize($subscription, 'json', ['groups' => 'subscription:read']);
        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['company_id']) || !isset($data['debut']) || !isset($data['end'])) {
            return $this->json(['message' => 'Missing required fields (company_id or debut or end)'], 400);
        }


        $company = $this->companyRepository->findOneBy(['id' => $data['company_id']]);

        if (!$company) {
            return new JsonResponse(['status' => 0, 'message' => 'Company not found'], JsonResponse::HTTP_NOT_FOUND);
        }


        $subscription = new Subscription();
        $subscription->setDebut(new \DateTime($data['debut'] ?? 'now'));
        $subscription->setEnd(new \DateTime($data['end'] ?? 'now'));
        $subscription->setType($data['type'] ?? 'standard');
        $subscription->setStatus($data['status'] ?? 'enabled');
        $subscription->setCompany($company);

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return $this->json($subscription, Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->find($id);
        if (!$subscription) {
            return $this->json(['message' => 'Subscription not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $subscription->setDebut(isset($data['debut']) ? new \DateTime($data['debut']) : $subscription->getDebut());
        $subscription->setEnd(isset($data['end']) ? new \DateTime($data['end']) : $subscription->getEnd());
        $subscription->setType($data['type'] ?? $subscription->getType());
        $subscription->setStatus($data['status'] ?? $subscription->getStatus());
        
        $this->entityManager->flush();
        return $this->json($subscription);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->find($id);
        if (!$subscription) {
            return $this->json(['message' => 'Subscription not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($subscription);
        $this->entityManager->flush();
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/company/{id}', methods: ['GET'])]
public function getMostRecentActiveOrExpiredSubscription(int $id): JsonResponse
{
    // Retrieve all subscriptions for the company
    $subscriptions = $this->subscriptionRepository->findBy(
        ['company' => $id]
    );

    // If no subscription, create a new one
    if (!$subscriptions) {
        $subscription = new Subscription();
        $subscription->setDebut(new \DateTime()); // Set start date as now
        $subscription->setEnd((new \DateTime())->modify('+6 months')); // Set end date as six months from now
        $subscription->setCompany($this->companyRepository->find($id));
        $subscription->setType('standard'); // Default type
        $subscription->setStatus('enabled'); // Set status as enabled
        $subscription->setCreatedAt(new \DateTime()); // Set creation date as now

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        // Serialize the new subscription to return
        $data = $this->serializer->serialize($subscription, 'json', ['groups' => 'subscription:read']);
        return new JsonResponse($data, JsonResponse::HTTP_CREATED, [], true);
    }


    // Check and update status if the end date is past
    $today = new \DateTime();
    foreach ($subscriptions as $subscription) {
        if ($subscription->getStatus() === 'enabled' && $subscription->getEnd() <= $today) {
            $subscription->setStatus('expired');
            $this->entityManager->persist($subscription);
        }
    }
    $this->entityManager->flush();

    // Retrieve the most recent enabled subscription
    $subscriptionToReturn = $this->subscriptionRepository->findOneBy(
        ['company' => $id, 'status' => 'enabled'],
        ['debut' => 'DESC'] // Order by start date descending to get the most recent
    );

    // if there is no active susbscription return the most recent expired
    if (!$subscriptionToReturn) {
        $subscriptionToReturn = $this->subscriptionRepository->findOneBy(
            ['company' => $id, 'status' => 'expired'],
            ['debut' => 'DESC'] // Order by start date descending to get the most recent
        );
    }
    
    // Serialize the found subscription if available
    $data = $this->serializer->serialize($subscriptionToReturn, 'json', ['groups' => 'subscription:read']);
    return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
}

}
