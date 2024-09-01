<?php

namespace App\Controller;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Repository\SubscriptionRepository;
use App\Entity\Subscription;

#[Route('/api/companies')]
class CompanyController extends AbstractController
{
    private $entityManager;
    private $serializer;
    private ValidatorInterface $validator;
    private CompanyRepository $companyRepository;
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, 
    ValidatorInterface $validator, CompanyRepository $companyRepository,  
    SubscriptionRepository $subscriptionRepository,)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->companyRepository = $companyRepository;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    #[Route('', methods: ['GET'])]
    public function index(CompanyRepository $companyRepository): JsonResponse
    {
        $companies = $companyRepository->findAll();
        $data = $this->serializer->serialize($companies, 'json', ['groups' => 'company:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return new JsonResponse(['status' => -1, 'message' => 'Email is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $existingCompany = $this->companyRepository->findOneBy(['email' => $data['email']]);
        if ($existingCompany) {
            return new JsonResponse(['status' => 0, 'message' => 'Email already exists'], JsonResponse::HTTP_CONFLICT);
        }


        $company = new Company();
        $company->setCode($this->generateCode());
        $company->setName($data['name']);
        $company->setTel($data['tel'] ?? null);
        $company->setEmail($data['email']);
        $company->setCity($data['city'] ?? null);
        $company->setCountry($data['country'] ?? null);
        $company->setAddressDetails($data['address_details'] ?? null);
        $company->setStatus($data['status'] ?? 'draft');
        $company->setCreatedAt(new \DateTime());

        $errors = $this->validator->validate($company);

        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return new JsonResponse(['status' => -1, 'message' => $errorsString], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($company);

        
        // create subscription
        $subscription = new Subscription();
        $debutDate = new \DateTime($data['debut'] ?? 'now');
        $subscription->setDebut($debutDate);
        // Add 6 months to the debut date for the end date
        $endDate = (clone $debutDate)->modify('+6 months');
        $subscription->setEnd($endDate);
        $subscription->setType($data['type'] ?? 'standard');
        $subscription->setStatus($data['status'] ?? 'enabled');
        $subscription->setCompany($company);
        $subscription->setCreatedAt(new \DateTime()); // Set creation date as now
        $this->entityManager->persist($subscription);

        $this->entityManager->flush();

        $data = $this->serializer->serialize($company, 'json', ['groups' => 'company:read']);
        return new JsonResponse($data, JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $company = $this->companyRepository->find($id);

        if (!$company) {
            return new JsonResponse(['status' => 0, 'message' => 'Company not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($company, 'json', ['groups' => 'company:read']);
        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $company = $this->companyRepository->find($id);

        if (!$company) {
            return new JsonResponse(['status' => 0, 'message' => 'Company not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $company->setName($data['name'] ?? $company->getName());
        $company->setTel($data['tel'] ?? $company->getTel());
        $company->setEmail($data['email'] ?? $company->getEmail());
        $company->setCity($data['city'] ?? $company->getCity());
        $company->setCountry($data['country'] ?? $company->getCountry());
        $company->setAddressDetails($data['address_details'] ?? $company->getAddressDetails());
        $company->setStatus($data['status'] ?? $company->getStatus());
        $company->setCode($data['code'] ?? $company->getCode());

        $errors = $this->validator->validate($company);

        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return new JsonResponse(['status' => -1, 'message' => $errorsString], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $data = $this->serializer->serialize($company, 'json', ['groups' => 'company:read']);
        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $company = $this->companyRepository->find($id);

        if (!$company) {
            return new JsonResponse(['status' => 0, 'message' => 'Company not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($company);
        $this->entityManager->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }


    function generateCode()
    {
        $nbChar = 4;
        $code = str_pad(rand(0, pow(10, $nbChar) - 1), $nbChar, '0', STR_PAD_LEFT);
        $existingCompany = $this->companyRepository->findOneBy(['code' => $code]);
        if ($existingCompany) {
            $this->generateCode();
        }
        return $code;
    }
}
