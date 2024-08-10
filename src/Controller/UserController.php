<?php

// src/Controller/UserController.php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\CompanyRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\ItemCategory;
use App\Entity\BusinessPartner;

class UserController extends AbstractController
{
    private $entityManager;
    private $userRepository;
    private CompanyRepository $companyRepository;
    private $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        CompanyRepository $companyRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->companyRepository = $companyRepository;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/api/users', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        return $this->json($users);
    }

    #[Route('/api/users/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        return $this->json($user);
    }

    #[Route('/api/users', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['company_code'])) {
            return $this->json(['message' => 'Missing company code '], 400);
        }

        if (!isset($data['email']) || !isset($data['name']) || !isset($data['password'])) {
            return $this->json(['message' => 'Missing required fields (email or name or password)'], 400);
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['message' => 'Email already exists'], 400);
        }


        $company = $this->companyRepository->findOneBy(['code' => $data['company_code']]);

        if (!$company) {
            return new JsonResponse(['status' => 0, 'message' => 'Company not found'], JsonResponse::HTTP_NOT_FOUND);
        }


        $usersForCompany = $this->userRepository->findBy(['company' => $company]);
        $isFirstUser = count($usersForCompany) === 0;

        $user = new User();
        $user->setEmail($data['email']);
        $user->setName($data['name']);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword); // In a real app, make sure to hash the password!
        $user->setRole($isFirstUser ? 'admin' : ($data['role'] ?? 'user'));
        $user->setStatus($data['status'] ?? 'enabled');
        $user->setCompany($company);

        $this->entityManager->persist($user);

        // If it's the first user, also create a default item category and business partener
        if ($isFirstUser) {
            // item category
            $itemCategory = new ItemCategory();
            $itemCategory->setName('GENERAL');
            $itemCategory->setCompany($company);
            $itemCategory->setUser($user);
            $itemCategory->setCreatedAt(new \DateTime());
            $this->entityManager->persist($itemCategory);

             // business partner
             $businessPartner = new BusinessPartner();
             $businessPartner->setName('GENERAL');
             $businessPartner->setCompany($company);
             $businessPartner->setUser($user);
             $businessPartner->setType('GENERAL');
             $businessPartner->setCreatedAt(new \DateTime());
             $this->entityManager->persist($businessPartner);
        }

        $this->entityManager->flush();

        return $this->json($user, 201);
    }

    #[Route('/api/users/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        /*if (isset($data['email'])) {
            $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser && $existingUser->getId() !== $id) {
                return $this->json(['message' => 'Email already exists'], 400);
            }
            $user->setEmail($data['email']);
        }*/

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        if (isset($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        if (isset($data['role'])) {
            $user->setRole($data['role']);
        }

        if (isset($data['status'])) {
            $user->setStatus($data['status']);
        }

        if (isset($data['company_id'])) {
            $company = $this->companyRepository->find($data['company_id']);
            if (!$company) {
                return new JsonResponse(['status' => 0, 'message' => 'Company not found'], JsonResponse::HTTP_NOT_FOUND);
            }
            $user->setCompany($company);
        }

        $this->entityManager->flush();

        return $this->json($user);
    }

    #[Route('/api/users/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'User deleted successfully']);
    }

    #[Route('api/login', name: 'user_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password']) || empty($data['company_code'])) {
            return $this->json(['status' => 0, 'message' => 'Email, password and company code are required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $company = $this->companyRepository->findOneBy(['code' => $data['company_code']]);
        if (!$company) {
            return new JsonResponse(['status' => 0, 'message' => 'Company not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email'], 'company' => $company]);
        if (!$user) {
            return $this->json(['status' => 0, 'message' => 'Invalid email for this company'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['status' => 0, 'message' => 'Invalid password'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $this->json($user);
    }
}
