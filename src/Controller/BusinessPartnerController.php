<?php
namespace App\Controller;

use App\Entity\BusinessPartner;
use App\Repository\BusinessPartnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\CompanyRepository;
use App\Repository\UserRepository;
use Symfony\Component\Serializer\SerializerInterface;


#[Route('/api/businesspartners')]
class BusinessPartnerController extends AbstractController
{
    private $entityManager;
    private $businessPartnerRepository;
    private CompanyRepository $companyRepository;
    private UserRepository $userRepository;
    private $serializer;

    public function __construct(EntityManagerInterface $entityManager, BusinessPartnerRepository $businessPartnerRepository, 
    CompanyRepository $companyRepository, UserRepository $userRepository, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->businessPartnerRepository = $businessPartnerRepository;
        $this->companyRepository = $companyRepository;
        $this->userRepository = $userRepository;
        $this->serializer = $serializer;
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $businessPartners = $this->businessPartnerRepository->findAll();
        $data = $this->serializer->serialize($businessPartners, 'json', ['groups' => 'businesspartner:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $businessPartner = $this->businessPartnerRepository->find($id);

        if (!$businessPartner) {
            return $this->json(['message' => 'Business Partner not found'], 404);
        }

        return $this->json($businessPartner);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        
        $data = json_decode($request->getContent(), true);

        if (!isset($data['company_id']) || !isset($data['name']) || !isset($data['user_id'])) {
            return $this->json(['message' => 'Missing required fields (company_id or name or user_id)'], 400);
        }
        
        $existingInstance = $this->businessPartnerRepository->findOneBy(['name' => $data['name'], 'company' => $data['company_id']]);
        if ($existingInstance) {
            return $this->json(['message' => 'Partner already exists for this company'], JsonResponse::HTTP_CONFLICT);
        }

        
        $company = $this->companyRepository->findOneBy(['id' => $data['company_id']]);
      

        if (!$company) {
            return new JsonResponse(['status' => 0, 'message' => 'Company not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        
        $user = $this->userRepository->findOneBy(['id' => $data['user_id']]);

        if (!$user) {
            return new JsonResponse(['status' => 0, 'message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }
      

        $instance = new BusinessPartner();
        $instance->setName($data['name']);
        $instance->setTel($data['tel'] ?? null);
        $instance->setEmail($data['email']?? "");
        $instance->setCity($data['city'] ?? null);
        $instance->setCountry($data['country'] ?? null);
        $instance->setAddress($data['address'] ?? null);
        $instance->setType($data['type'] ?? 'CUSTOMER');
        $instance->setCompany($company); 
        $instance->setUser($user);
        $instance->setCreatedAt(new \DateTime());

        $this->entityManager->persist($instance);
        $this->entityManager->flush();

        return $this->json($instance, 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $instance = $this->businessPartnerRepository->find($id);

        if (!$instance) {
            return $this->json(['message' => 'Partner not found'], 404);
        }

        $data = json_decode($request->getContent(), true);


       /* if (isset($data['name']) && isset($data['company_id'])) {
            $existingItemCategory = $this->businessPartnerRepository->findOneBy(['name' => $data['name'], 'company' => $data['company_id']]);
            if ($existingItemCategory) {
            return $this->json(['message' => 'Name already exists for this company'], 400);
            }
        }*/

        $instance->setName($data['name']?? $instance->getName());    
        $instance->setTel($data['tel'] ?? $instance->getTel());
        $instance->setEmail($data['email'] ?? $instance->getEmail());
        $instance->setCity($data['city'] ?? $instance->getCity());
        $instance->setCountry($data['country'] ?? $instance->getCountry());
        $instance->setAddress($data['address'] ?? $instance->getAddress());
        $instance->setType($data['type'] ?? $instance->getType());

        $this->entityManager->flush();

        return $this->json($instance);
    }


    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $instance = $this->businessPartnerRepository->find($id);

        if (!$instance) {
            return new JsonResponse(['status' => 0, 'message' => 'Partner not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($instance);
        $this->entityManager->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/company/{id}', methods: ['GET'])]
    public function findPartnersByCompany(int $id): JsonResponse
    {

        $partners = $this->businessPartnerRepository->findBy( ['company' => $id ]);

        if (!$partners) {
            return $this->json(['message' => 'partners not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($partners, 'json', ['groups' => 'businesspartner:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }
}