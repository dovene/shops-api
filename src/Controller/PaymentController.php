<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventItem;
use App\Entity\Payment;
use App\Repository\EventRepository;
use App\Repository\ItemRepository;
use App\Repository\BusinessPartnerRepository;
use App\Repository\CompanyRepository;
use App\Repository\EventTypeRepository;
use App\Repository\PaymentRepository;
use App\Repository\PaymentTypeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/payments')]
class PaymentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private EventRepository $eventRepository;
    private ItemRepository $itemRepository;
    private BusinessPartnerRepository $businessPartnerRepository;
    private CompanyRepository $companyRepository;
    private EventTypeRepository $eventTypeRepository;
    private UserRepository $userRepository;
    private SerializerInterface $serializer;
    private PaymentRepository $paymentRepository;
    private PaymentTypeRepository $paymentTypeRepository;


    public function __construct(
        EntityManagerInterface $entityManager,
        EventRepository $eventRepository,
        ItemRepository $itemRepository,
        BusinessPartnerRepository $businessPartnerRepository,
        CompanyRepository $companyRepository,
        EventTypeRepository $eventTypeRepository,
        UserRepository $userRepository,
        SerializerInterface $serializer,
        PaymentRepository $paymentRepository,
        PaymentTypeRepository $paymentTypeRepository,
    ) {
        $this->entityManager = $entityManager;
        $this->eventRepository = $eventRepository;
        $this->itemRepository = $itemRepository;
        $this->businessPartnerRepository = $businessPartnerRepository;
        $this->companyRepository = $companyRepository;
        $this->eventTypeRepository = $eventTypeRepository;
        $this->userRepository = $userRepository;
        $this->serializer = $serializer;
        $this->paymentRepository = $paymentRepository;
        $this->paymentTypeRepository = $paymentTypeRepository;
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $items = $this->paymentRepository->findAll();
        $data = $this->serializer->serialize($items, 'json', [
            'groups' => 'event:read',
            'payment:read',
            'company:read'
        ]);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $item = $this->paymentRepository->find($id);

        if (!$item) {
            return $this->json(['message' => 'Payment not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($item, 'json', ['groups' => 'payment:read', 'event:read']);
        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }



    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['event_id']) || !isset($data['payment_type_id']) || !isset($data['user_id']) || !isset($data['amount'] )) {
            return $this->json(['message' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
        }


        $event = $this->eventRepository->find($data['event_id']);
        $paymentType = $this->paymentTypeRepository->find($data['payment_type_id']);
        $user = $this->userRepository->find($data['user_id']);

        if (!$event || !$paymentType  || !$user) {
            return $this->json(['message' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $instance = new Payment();
        $instance->setPaymentDate(new \DateTime($data['event_date'] ?? 'now'));
        $instance->setAmount($data['amount'] ?? null);
        $instance->setUser($user);
        $instance->setPaymentType($paymentType);
        $instance->setEvent($event);
        $instance->setCreatedAt(new \DateTime());

      
        $this->entityManager->persist($instance);
        $this->entityManager->flush();

     
        $data = $this->serializer->serialize($instance, 'json', ['groups' => 'payment:read']);
        return new JsonResponse($data, JsonResponse::HTTP_CREATED, [], true);
    }

   
    #[Route('/event/{id}', methods: ['GET'])]
    public function findPaymentsByEvent(int $id): JsonResponse
    {
        $events = $this->paymentRepository->findBy(
            ['event' => $id],          // Criteria to filter by event id
            ['paymentDate' => 'DESC']      // Order by the 'createdAt' field in descending order
        );

        $data = $this->serializer->serialize($events, 'json', ['groups' => 'payment:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

}
