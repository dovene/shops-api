<?php

namespace App\Controller;

use App\Entity\AppMinimalVersion;
use App\Repository\AppMinimalVersionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/app_minimal_versions')]
class AppMinimalVersionController extends AbstractController
{
    private AppMinimalVersionRepository $repository;
    private EntityManagerInterface $entityManager;

    public function __construct(AppMinimalVersionRepository $repository, EntityManagerInterface $entityManager)
    {
        $this->repository = $repository;
        $this->entityManager = $entityManager;
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $appVersions = $this->repository->findAll();
        return $this->json($appVersions);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $appVersion = $this->repository->find($id);
        if (!$appVersion) {
            return $this->json(['message' => 'Not Found'], JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->json($appVersion);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $appVersion = new AppMinimalVersion();
        $appVersion->setAppId($data['app_id'] ?? null);
        $appVersion->setAppVersion($data['app_version'] ?? null);
        $appVersion->setAppOs($data['app_os'] ?? null);
        $appVersion->setAppName($data['app_name'] ?? null);
        $appVersion->setIsMinimalVersionMandatory($data['is_minimal_version_andatory'] ?? false);

        $this->entityManager->persist($appVersion);
        $this->entityManager->flush();

        return $this->json($appVersion, JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $appVersion = $this->repository->find($id);
        if (!$appVersion) {
            return $this->json(['message' => 'Not Found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $appVersion->setAppId($data['app_id'] ?? $appVersion->getAppId());
        $appVersion->setAppVersion($data['app_version'] ?? $appVersion->getAppVersion());
        $appVersion->setAppOs($data['app_os'] ?? $appVersion->getAppOs());
        $appVersion->setAppName($data['app_name'] ?? $appVersion->getAppName());
        $appVersion->setIsMinimalVersionMandatory($data['is_minimal_version_andatory'] ?? $appVersion->isMinimalVersionMandatory());

        $this->entityManager->flush();

        return $this->json($appVersion);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $appVersion = $this->repository->find($id);
        if (!$appVersion) {
            return $this->json(['message' => 'Not Found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($appVersion);
        $this->entityManager->flush();

        return $this->json(['message' => 'Deleted Successfully']);
    }

   

    #[Route('/by-os-id/find', methods: ['GET'])]
    public function findByAppOsAndId(Request $request): JsonResponse
    {
        $appId = $request->query->get('app_id');
        $appOs = $request->query->get('app_os');

        $appVersion = $this->repository->findOneBy(['appId' => $appId, 'appOs' => $appOs]);
        if (!$appVersion) {
            return $this->json(['message' => 'App version not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json($appVersion);
    }
    
}