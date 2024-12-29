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
use Symfony\Component\Mailer\MailerInterface;
use App\Entity\ItemCategory;
use App\Entity\BusinessPartner;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class UserController extends AbstractController
{
    private $entityManager;
    private $userRepository;
    private CompanyRepository $companyRepository;
    private $passwordHasher;
    private $serializer;
    private $mailer;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        CompanyRepository $companyRepository,
        UserPasswordHasherInterface $passwordHasher,
        SerializerInterface $serializer,
        MailerInterface $mailer,
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->companyRepository = $companyRepository;
        $this->passwordHasher = $passwordHasher;
        $this->serializer = $serializer;
        $this->mailer = $mailer;
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
            return $this->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        return $this->json($user);
    }

    #[Route('/api/users', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['company_code'])) {
            return $this->json(['message' => 'Code de la boutique manquant '], 400);
        }

        if (!isset($data['email']) || !isset($data['name']) || !isset($data['password'])) {
            return $this->json(['message' => 'Données manquantes (email ou nom ou mot de passe)'], 400);
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['message' => 'Cet email existe déjà'], 400);
        }


        $company = $this->companyRepository->findOneBy(['code' => $data['company_code']]);

        if (!$company) {
            return new JsonResponse(['status' => 0, 'message' => 'Entreprise non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }


        $usersForCompany = $this->userRepository->findBy(['company' => $company]);
        $isFirstUser = count($usersForCompany) === 0;

        $user = new User();
        $user->setEmail($data['email']);
        $user->setName($data['name']);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword); // In a real app, make sure to hash the password!
        $user->setRole($isFirstUser ? 'owner' : ($data['role'] ?? 'user'));
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
                return new JsonResponse(['status' => 0, 'message' => 'Entreprise non trouvé'], JsonResponse::HTTP_NOT_FOUND);
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
            return $this->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'Utilisateur correctement supprimé']);
    }

    #[Route('api/login', name: 'user_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password']) || empty($data['company_code'])) {
            return $this->json(['status' => 0, 'message' => ' Données manquantes : l\'email, le mot de passe et le code de la boutique sont obligatoires'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $company = $this->companyRepository->findOneBy(['code' => $data['company_code']]);
        if (!$company) {
            return new JsonResponse(['status' => 0, 'message' => 'Entreprise non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email'], 'company' => $company]);
        if (!$user) {
            return $this->json(['status' => 0, 'message' => 'Email invalide pour cette boutique'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['status' => 0, 'message' => 'Invalid password'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        
        if ($user->getStatus() != 'enabled') {
            return $this->json(['status' => 0, 'message' => 'Cette utilisateur ne peut pas se connecter - statut non actif'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $this->json($user);
    }

    #[Route('/api/users/company/{id}', methods: ['GET'])]
    public function findUsersByCompany(int $id): JsonResponse
    {
        // Retrieve partners sorted by name
        $instances = $this->userRepository->findBy(
            ['company' => $id],        // Criteria to filter by company id
            ['name' => 'ASC']          // Order by the 'name' field in ascending order
        );

        $data = $this->serializer->serialize($instances, 'json', ['groups' => 'user:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }


    #[Route('/api/users/anonymize/{id}', methods: ['PATCH'])]
    public function anonymize(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['message' => 'Utilisateur non existant'], 404);
        }

        $user->setStatus('deleted');
        $user->setEmail($user->getEmail().'-DELETED');
        $user->setName($user->getName().'-DELETED');

        $this->entityManager->flush();

        return $this->json($user);
    }

    #[Route('api/forgot-password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse([
                'status' => 0,
                'message' => 'Email is required'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Find user by email
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse([
                'status' => 0,
                'message' => 'Aucun utilisateur trouvé avec cet email'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Generate reset token (simple implementation using random_bytes)
        $resetToken = bin2hex(random_bytes(32));
        $tokenExpiry = new \DateTime('+1 hour');

        // Store token in database
        $user->setResetToken($resetToken);
        $user->setResetTokenExpiry($tokenExpiry);
        $this->entityManager->flush();

        // Send reset email
        $this->sendResetEmail($user, $resetToken);

        return new JsonResponse([
            'status' => 1,
            'message' => 'Les instructions de récupération du mot de passe ont été envoyées à votre adresse email'
        ]);
    }

    #[Route('api/reset-password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $newPassword = $data['password'] ?? null;

        if (!$token || !$newPassword) {
            return new JsonResponse([
                'status' => 0,
                'message' => 'Le jeton et le nouveau mot de passe sont requis'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Find user by reset token
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user) {
            return new JsonResponse([
                'status' => 0,
                'message' => 'Jeton de récupération de mot de passe invalide'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Check if token has expired
        if ($user->getResetTokenExpiry() < new \DateTime()) {
            return new JsonResponse([
                'status' => 0,
                'message' => 'Le délai de récupération a expiré - veuillez refaire la demande de réinitialisation du mot de passe'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Update password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        
        // Clear reset token
        $user->setResetToken(null);
        $user->setResetTokenExpiry(null);

        $this->entityManager->flush();

        return new JsonResponse([
            'status' => 1,
            'message' => 'Le mot de passe a été réinitialisé avec succès'
        ]);
    }

    private function sendResetEmail(User $user, string $token): void
    {
        $resetUrl = "https://shopiques.inaxxe.com/reset-password?token=" . $token;

        $email = (new Email())
            ->from(new Address('office@inaxxe.com', 'Shopiques'))
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe Shopiques')
            ->html(
                "<p>Bonjour {$user->getName()},</p>" .
                "<p>Vous avez demandé la réinitialisation de votre mot de passe Shopiques.</p>" .
                "<p>Pour définir un nouveau mot de passe, veuillez cliquer sur le lien suivant (valable pendant 1 heure) :</p>" .
                "<p><a href='{$resetUrl}'>Réinitialiser mon mot de passe</a></p>" .
                "<p>Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.</p>" .
                "<p>Cordialement,<br>L'équipe Shopiques.</p>"
            );

        $this->mailer->send($email);
    }
}
