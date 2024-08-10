<?php
namespace App\Controller;

use App\Entity\Item;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\CompanyRepository;
use App\Repository\ItemCategoryRepository;
use App\Repository\UserRepository;
use Symfony\Component\Serializer\SerializerInterface;


#[Route('/api/items')]
class ItemController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ItemRepository $itemRepository;
    private CompanyRepository $companyRepository;
    private UserRepository $userRepository;
    private ItemCategoryRepository $itemCategoryRepository;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $entityManager, ItemRepository $itemRepository, 
    CompanyRepository $companyRepository, UserRepository $userRepository, SerializerInterface $serializer, 
    ItemCategoryRepository $itemCategoryRepository)
    {
        $this->entityManager = $entityManager;
        $this->itemRepository = $itemRepository;
        $this->companyRepository = $companyRepository;
        $this->userRepository = $userRepository;
        $this->itemCategoryRepository = $itemCategoryRepository;
        $this->serializer = $serializer;
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $items = $this->itemRepository->findAll();
        $data = $this->serializer->serialize($items, 'json', ['groups' => 'item:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $Item = $this->itemRepository->find($id);

        if (!$Item) {
            return $this->json(['message' => 'Item not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json($Item);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        
        $data = json_decode($request->getContent(), true);

        if (!isset($data['company_id']) || !isset($data['name']) || !isset($data['reference']) || !isset($data['user_id']) || !isset($data['item_category_id'])) {
            return $this->json(['message' => 'Missing required fields (company_id or name or user_id or item_category_id or reference)'], JsonResponse::HTTP_BAD_REQUEST);
        }
        
        $existingInstance = $this->itemRepository->findOneBy(['name' => $data['name'], 'company' => $data['company_id']]);
        if ($existingInstance) {
            return $this->json(['message' => 'Item name already exists for this company'], JsonResponse::HTTP_CONFLICT);
        }

        $existingInstance = $this->itemRepository->findOneBy(['reference' => $data['reference'], 'company' => $data['company_id']]);
        if ($existingInstance) {
            return $this->json(['message' => 'Item reference already exists for this company'], JsonResponse::HTTP_CONFLICT);
        }

        
        $company = $this->companyRepository->findOneBy(['id' => $data['company_id']]);
        if (!$company) {
            return new JsonResponse(['status' => 0, 'message' => 'Company not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        
        $user = $this->userRepository->findOneBy(['id' => $data['user_id']]);
        if (!$user) {
            return new JsonResponse(['status' => 0, 'message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $category = $this->itemCategoryRepository->findOneBy(['id' => $data['item_category_id']]);
        if (!$category) {
            return new JsonResponse(['status' => 0, 'message' => 'Category not found'], JsonResponse::HTTP_NOT_FOUND);
        }
      

        $instance = new Item();
        $instance->setName($data['name']);
        $instance->setReference($data['reference']);
        $instance->setSellPrice($data['sell_price']?? null);
        $instance->setBuyPrice($data['buy_price'] ?? null);
        $instance->setpicture($data['picture'] ?? null);
        $instance->setCompany($company); 
        $instance->setUser($user);
        $instance->setItemCategory($category);
        $instance->setCreatedAt(new \DateTime());

        $this->entityManager->persist($instance);
        $this->entityManager->flush();

        return $this->json($instance, JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $instance = $this->itemRepository->find($id);

        if (!$instance) {
            return $this->json(['message' => 'Item not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $company = $this->companyRepository->findOneBy(['id' => $data['company_id']]);
        if (!$company) {
            return new JsonResponse(['status' => 0, 'message' => 'Company not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        
        $user = $this->userRepository->findOneBy(['id' => $data['user_id']]);
        if (!$user) {
            return new JsonResponse(['status' => 0, 'message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $category = $this->itemCategoryRepository->findOneBy(['id' => $data['item_category_id']]);
        if (!$category) {
            return new JsonResponse(['status' => 0, 'message' => 'Category not found'], JsonResponse::HTTP_NOT_FOUND);
        }
      

        $instance->setName($data['name']?? $instance->getName());    
        $instance->setReference($data['reference'] ?? $instance->getReference());
        $instance->setSellPrice($data['sell_price'] ?? $instance->getSellPrice());
        $instance->setBuyPrice($data['buy_price'] ?? $instance->getBuyPrice());
        $instance->setPicture($data['picture'] ?? $instance->getPicture());
        $instance->setCompany($company); 
        $instance->setUser($user);
        $instance->setItemCategory($category);
        $instance->setCreatedAt(new \DateTime());

        $this->entityManager->flush();

        return $this->json($instance);
    }


    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $instance = $this->itemRepository->find($id);

        if (!$instance) {
            return new JsonResponse(['status' => 0, 'message' => 'Item not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($instance);
        $this->entityManager->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }


    #[Route('/company/{id}', methods: ['GET'])]
    public function findItemsByCompany(int $id): JsonResponse
    {

        $items = $this->itemRepository->findBy(
            ['company' => $id],        // Criteria to filter by company id
            ['name' => 'ASC']          // Order by the 'name' field in ascending order
        );


        $data = $this->serializer->serialize($items, 'json', ['groups' => 'item:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }
}