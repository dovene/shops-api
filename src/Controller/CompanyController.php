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
use Symfony\Component\Mime\Address;
use App\Repository\EventRepository;

#[Route('/api/companies')]
class CompanyController extends AbstractController
{
    private $entityManager;
    private $serializer;
    private ValidatorInterface $validator;
    private CompanyRepository $companyRepository;
    private SubscriptionRepository $subscriptionRepository;
    private EventRepository $eventRepository;
    private MailerInterface $mailer;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, 
    ValidatorInterface $validator, CompanyRepository $companyRepository,  
    SubscriptionRepository $subscriptionRepository,MailerInterface $mailer, EventRepository $eventRepository)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->companyRepository = $companyRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->eventRepository = $eventRepository;
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
            ->from(new Address('office@inaxxe.com', 'Shopiques'))
            ->to($company->getEmail()) 
            ->subject('Boostez votre entreprise avec Shopiques!')
            ->bcc('dovene.developer@gmail.com')  // Send as BCC (hidden)
            ->bcc('dagogue@yahoo.fr')  // Send BCC (hidden)
            ->subject('Bienvenue! Votre société a été créée')
            ->text("Bonjour " . $company->getName() . ",\n\nVotre société a été correctement créée. Voici le code de votre boutique: " . $company->getCode() .
             "\nPartager ce code avec vos collaborateurs afin qu'ils puissent s'inscrire et travailler avec vous dans la même boutique.".
             "\n\nPour toute question concernant l'utilisation de l'application, contactez-nous au +33660506626 (utiliser de préférence whatsapp) ou par mail office@inaxxe.com.". 
             "\n\nCordialement,\n\nL'équipe Shopiques.");

        // Send the email
        $this->mailer->send($email);
    }

    #[Route('/remind-no-transaction', methods: ['POST'])]
    public function remindCompaniesNoTransaction(): JsonResponse
    {
        // Find companies that have not recorded any transaction/event
        $companies = $this->companyRepository->findAll();

        $companiesWithoutEvents = array_filter($companies, function ($company) {
            $events = $this->eventRepository->findBy(['company' => $company]);
            return count($events) === 0;
        });

        foreach ($companiesWithoutEvents as $company) {
            $this->sendReminderEmail($company);
        }

        return new JsonResponse([
            'status' => 1,
            'message' => 'Reminder emails have been sent to companies without transactions.',
            'count' => count($companiesWithoutEvents)
        ], JsonResponse::HTTP_OK);
    }

    private function sendReminderEmail(Company $company): void
    {
        /* Uncomment for testing
        $allowedEmails = ['adovene@gmail.com', 'dagogue@yahoo.fr'];
    
        // Check if the company's email is in the allowed list
        if (!in_array($company->getEmail(), $allowedEmails)) {
            return;
        }*/
    
        $playStoreUrl = 'https://play.google.com/store/apps/details?id=com.dov.shopique&pli=1';
        $appStoreUrl = 'https://apps.apple.com/us/app/shopiques/id6664070531';
        $webUrl = 'https://shopiques-app.inaxxe.com';
    
        $email = (new Email())
            ->from(new Address('office@inaxxe.com', 'Shopiques'))
            ->to($company->getEmail())
            ->subject('Boostez votre entreprise avec Shopiques!')
            ->html(
                "<p>Bonjour " . $company->getName() . ",</p>" .
                "<p>Nous avons remarqué que vous n'avez pas encore enregistré de transaction dans votre application Shopiques.<br>Enregistrez vos transactions pour tirer pleinement parti de la puissance de votre application afin de maîtriser votre commerce et booster votre entreprise.</p>" .
                "<p>Le guide utilisateur est disponible <a href='https://shopiques.inaxxe.com/#user-guide'>ici</a> pour vous accompagner dans vos premières saisies d'articles et de transactions.</p>" .
                "<p>Relancez votre application: <br>" .
                "<a href='$playStoreUrl'>Android</a> | <a href='$appStoreUrl'>iOS</a> | <a href='$webUrl'>Web mobile</a></p>" .
                "<p>Si vous avez des questions ou avez besoin d'aide, n'hésitez pas à nous contacter au +33660506626 (utiliser de préférence WhatsApp) ou par mail à <a href='mailto:office@inaxxe.com'>office@inaxxe.com</a>.</p>" .
                "<p>Cordialement,<br><br>L'équipe Shopiques.</p>"
            );
    
        // Send the reminder email
        $this->mailer->send($email);
    }    

}
