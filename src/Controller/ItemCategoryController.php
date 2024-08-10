<?php

// src/Controller/UserController.php

namespace App\Controller;

use App\Entity\ItemCategory;
use App\Repository\ItemCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\CompanyRepository;
use App\Repository\UserRepository;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/itemcategories')]
class ItemCategoryController extends AbstractController
{
    private $entityManager;
    private $itemCategoryRepository;
    private CompanyRepository $companyRepository;
    private UserRepository $userRepository;
    private $serializer;

    public function __construct(EntityManagerInterface $entityManager, ItemCategoryRepository $itemCategoryRepository, 
    CompanyRepository $companyRepository, UserRepository $userRepository, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->itemCategoryRepository = $itemCategoryRepository;
        $this->companyRepository = $companyRepository;
        $this->userRepository = $userRepository;
        $this->serializer = $serializer;
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $itemCategories = $this->itemCategoryRepository->findAll();
        $data = $this->serializer->serialize($itemCategories, 'json', ['groups' => 'itemcategory:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $itemCategory = $this->itemCategoryRepository->find($id);

        if (!$itemCategory) {
            return $this->json(['message' => 'ItemCategory not found'], 404);
        }

        return $this->json($itemCategory);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        
        $data = json_decode($request->getContent(), true);

        if (!isset($data['company_id']) || !isset($data['name']) || !isset($data['user_id'])) {
            return $this->json(['message' => 'Missing required fields (company_id or name or user_id)'], 400);
        }
        
        $existingItemCategory = $this->itemCategoryRepository->findOneBy(['name' => $data['name'], 'company' => $data['company_id']]);
        if ($existingItemCategory) {
            return $this->json(['message' => 'Name already exists for this company'], 400);
        }

        
        $company = $this->companyRepository->findOneBy(['id' => $data['company_id']]);
      

        if (!$company) {
            return new JsonResponse(['status' => 0, 'message' => 'Company not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        
        $user = $this->userRepository->findOneBy(['id' => $data['user_id']]);

        if (!$user) {
            return new JsonResponse(['status' => 0, 'message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }
      

        $itemCategory = new ItemCategory();
        $itemCategory->setName($data['name']);
        $itemCategory->setCompany($company); 
        $itemCategory->setUser($user);
        $itemCategory->setCreatedAt(new \DateTime());

        $this->entityManager->persist($itemCategory);
        $this->entityManager->flush();

        return $this->json($itemCategory, 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $itemCategory = $this->itemCategoryRepository->find($id);

        if (!$itemCategory) {
            return $this->json(['message' => 'item category not found'], 404);
        }

        $data = json_decode($request->getContent(), true);


        if (isset($data['name']) && isset($data['company_id'])) {
            $existingItemCategory = $this->itemCategoryRepository->findOneBy(['name' => $data['name'], 'company' => $data['company_id']]);
            if ($existingItemCategory) {
            return $this->json(['message' => 'Name already exists for this company'], 400);
            }
        }

        $itemCategory->setName($data['name']);       

        $this->entityManager->flush();

        return $this->json($itemCategory);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $itemCategory = $this->itemCategoryRepository->find($id);

        if (!$itemCategory) {
            return $this->json(['message' => 'item category not found'], 404);
        }

        $this->entityManager->remove($itemCategory);
        $this->entityManager->flush();

        return $this->json(['message' => 'item category deleted successfully']);
    }

    
    #[Route('/company/{id}', methods: ['GET'])]
    public function findItemCategoriesByCompany(int $id): JsonResponse
    {

        $itemCategories = $this->itemCategoryRepository->findBy(
            ['company' => $id],        // Criteria to filter by company id
            ['name' => 'ASC']          // Order by the 'name' field in ascending order
        );

      
        $data = $this->serializer->serialize($itemCategories, 'json', ['groups' => 'itemcategory:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

}
