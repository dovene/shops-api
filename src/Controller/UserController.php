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

class UserController extends AbstractController
{
    private $entityManager;
    private $userRepository;
    private CompanyRepository $companyRepository;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, 
    CompanyRepository $companyRepository, UserPasswordHasherInterface $passwordHasher)
    {
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

        if (!isset($data['email']) || !isset($data['name']) || !isset($data['password']) || !isset($data['company_id'])) {
            return $this->json(['message' => 'Missing required fields'], 400);
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['message' => 'Email already exists'], 400);
        }

        $company = $this->companyRepository->find($data['company_id']);

        if (!$company) {
            return new JsonResponse(['status' => 0, 'message' => 'Company not found'], JsonResponse::HTTP_NOT_FOUND);
        }


        $user = new User();
        $user->setEmail($data['email']);
        $user->setName($data['name']);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword); // In a real app, make sure to hash the password!
        $user->setRole($data['role'] ?? 'user');
        $user->setStatus($data['status'] ?? 'enabled');


        $user->setCompany($company);

        $this->entityManager->persist($user);
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

        if (isset($data['email'])) {
            $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser && $existingUser->getId() !== $id) {
                return $this->json(['message' => 'Email already exists'], 400);
            }
            $user->setEmail($data['email']);
        }

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

        if (empty($data['email']) || empty($data['password'])) {
            return $this->json(['status' => 0, 'message' => 'Email and password are required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);
        if (!$user) {
            return $this->json(['status' => 0, 'message' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['status' => 0, 'message' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Normally, here you would generate a JWT token and return it to the user
        // For simplicity, we will return the user details directly
        return $this->json(['status' => 1, 'message' => 'Login successful', 'user' => $user]);
    }
}
