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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/api/companies')]
class CompanyController extends AbstractController
{
    private $entityManager;
    private $serializer;
    private ValidatorInterface $validator;
    private CompanyRepository $companyRepository;
    private SubscriptionRepository $subscriptionRepository;
    private MailerInterface $mailer;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, 
    ValidatorInterface $validator, CompanyRepository $companyRepository,  
    SubscriptionRepository $subscriptionRepository,MailerInterface $mailer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->companyRepository = $companyRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->mailer = $mailer;
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
        $company->setCurrency($data['currency'] ?? null);
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
        $endDate = (clone $debutDate)->modify('+3 months');
        $subscription->setEnd($endDate);
        $subscription->setType($data['type'] ?? 'standard');
        $subscription->setStatus($data['status'] ?? 'enabled');
        $subscription->setCompany($company);
        $subscription->setCreatedAt(new \DateTime()); // Set creation date as now
        $this->entityManager->persist($subscription);

        $this->entityManager->flush();

         // Send email to the company with the generated code
         $this->sendCompanyCreatedEmail($company);

        
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
        $company->setCurrency($data['currency'] ?? $company->getCurrency());

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

    private function sendCompanyCreatedEmail(Company $company): void
    {
        $email = (new Email())
            ->from('office@inaxxe.com')  // Update to your domain
            ->to($company->getEmail()) 
            ->bcc('dovene.developer@gmail.com')  // Send as BCC (hidden)
            ->subject('Bienvenue! Votre société a été correctement créée')
            ->text("Bonjour " . $company->getName() . ",\n\nVotre société a été correctement créée. Voici le code de votre société: " . $company->getCode() . "\n\Pour toute question concernant l\'utilisation de l'application contactez-nous au +33660506626 (utiliser Whatsapp de préférence) ou par mail office@inaxxe.com. \n\Cordialement,\nShopiques");

        // Send the email
        $this->mailer->send($email);
    }
}
