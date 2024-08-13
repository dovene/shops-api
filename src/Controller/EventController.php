<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventItem;
use App\Repository\EventRepository;
use App\Repository\ItemRepository;
use App\Repository\BusinessPartnerRepository;
use App\Repository\CompanyRepository;
use App\Repository\EventTypeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/events')]
class EventController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private EventRepository $eventRepository;
    private ItemRepository $itemRepository;
    private BusinessPartnerRepository $businessPartnerRepository;
    private CompanyRepository $companyRepository;
    private EventTypeRepository $eventTypeRepository;
    private UserRepository $userRepository;
    private SerializerInterface $serializer;
    public function __construct(
        EntityManagerInterface $entityManager,
        EventRepository $eventRepository,
        ItemRepository $itemRepository,
        BusinessPartnerRepository $businessPartnerRepository,
        CompanyRepository $companyRepository,
        EventTypeRepository $eventTypeRepository,
        UserRepository $userRepository,
        SerializerInterface $serializer
    ) {
        $this->entityManager = $entityManager;
        $this->eventRepository = $eventRepository;
        $this->itemRepository = $itemRepository;
        $this->businessPartnerRepository = $businessPartnerRepository;
        $this->companyRepository = $companyRepository;
        $this->eventTypeRepository = $eventTypeRepository;
        $this->userRepository = $userRepository;
        $this->serializer = $serializer;
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $items = $this->eventRepository->findAll();
        $data = $this->serializer->serialize($items, 'json', [
            'groups' => 'event:read',
            'eventitem:read',
            'company:read'
        ]);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $item = $this->eventRepository->find($id);

        if (!$item) {
            return $this->json(['message' => 'Event not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($item, 'json', ['groups' => 'event:read']);
        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }



    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['event_type_id']) || !isset($data['business_partner_id']) || !isset($data['company_id']) || !isset($data['user_id']) || !isset($data['items'])) {
            return $this->json(['message' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (count($data['items']) === 0) {
            return $this->json(['message' => 'Event must have at least one event item'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $eventType = $this->eventTypeRepository->find($data['event_type_id']);
        $businessPartner = $this->businessPartnerRepository->find($data['business_partner_id']);
        $company = $this->companyRepository->find($data['company_id']);
        $user = $this->userRepository->find($data['user_id']);

        if (!$eventType || !$businessPartner || !$company || !$user) {
            return $this->json(['message' => 'Invalid foreign key references'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $event = new Event();
        $event->setEventDate(new \DateTime($data['event_date'] ?? 'now'));
        $event->setTva($data['tva'] ?? null);
        $event->setEventType($eventType);
        $event->setBusinessPartner($businessPartner);
        $event->setCompany($company);
        $event->setUser($user);
        $event->setCreatedAt(new \DateTime());
        $event->setStatus('VALIDATED'); // Default status

        // First persist the event to get the ID
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        // init computation var
        $totalQuantity = 0;
        $totalPrice = 0;

        // Now persist each EventItem with the event reference
        foreach ($data['items'] as $itemData) {
            $item = $this->itemRepository->find($itemData['item_id']);
            if (!$item) {
                return $this->json(['message' => 'Invalid item reference'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $eventItem = new EventItem();
            $eventItem->setQuantity($itemData['quantity']);
            $eventItem->setPrice($itemData['price'] ?? null);
            $eventItem->setEvent($event);
            $eventItem->setItem($item);

            $totalQuantity += $eventItem->getQuantity();
            $totalPrice += $eventItem->getPrice() * $eventItem->getQuantity();

            // Update item quantity based on event type
            if ($eventType->getIsAnIncreaseStockType() == 1) {
                $item->setQuantity($item->getQuantity() + $eventItem->getQuantity());
            } else {
                $item->setQuantity($item->getQuantity() - $eventItem->getQuantity());
            }

            $this->entityManager->persist($eventItem);
        }

        // Update event with total quantities and prices
        $event->setTotalQuantity($totalQuantity);
        $event->setTotalPrice($totalPrice);

        // Finally, flush all changes
        $this->entityManager->flush();

        $data = $this->serializer->serialize($event, 'json', ['groups' => 'event:read']);
        return new JsonResponse($data, JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['status'])) {
            return $this->json(['message' => 'Missing required field: status'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $validStatuses = ['VALIDATED', 'CANCELLED', 'WARNING'];
        if (!in_array($data['status'], $validStatuses)) {
            return $this->json(['message' => 'Invalid status'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $event = $this->eventRepository->find($id);
        if (!$event) {
            return $this->json(['message' => 'Event not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Check if we are cancelling the event and handle rollback
        if ($data['status'] === 'CANCELLED') {
            // Rollback the item quantities
            $eventItems = $event->getEventItems();
            $eventType = $event->getEventType();

            foreach ($eventItems as $eventItem) {
                $item = $eventItem->getItem();

                if ($eventType->getIsAnIncreaseStockType()) {
                    // Event was increasing stock, so rollback by decreasing quantity
                    $item->setQuantity($item->getQuantity() - $eventItem->getQuantity());
                } else {
                    // Event was decreasing stock, so rollback by increasing quantity
                    $item->setQuantity($item->getQuantity() + $eventItem->getQuantity());
                }

                $this->entityManager->persist($item);
            }
        }

        $event->setStatus($data['status']);
        $this->entityManager->flush();

        $data = $this->serializer->serialize($event, 'json', ['groups' => 'event:read']);
        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/company/{id}', methods: ['GET'])]
    public function findEventsByCompany(int $id): JsonResponse
    {

        // Retrieve events sorted by createdAt in descending order
        $events = $this->eventRepository->findBy(
            ['company' => $id],          // Criteria to filter by company id
            ['eventDate' => 'DESC']      // Order by the 'createdAt' field in descending order
        );

        $data = $this->serializer->serialize($events, 'json', ['groups' => 'event:read']);

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }


    #[Route('/dashboard/{companyId}', methods: ['GET'])]
public function getDashboardData(int $companyId): JsonResponse
{
    // Define today's date range
    $today = new \DateTime();
    $today->setTime(0, 0);

    // Define the current month's start
    $firstDayOfMonth = new \DateTime('first day of this month');
    $firstDayOfMonth->setTime(0, 0);

    // Define the current year's start
    $firstDayOfYear = new \DateTime('first day of January this year');
    $firstDayOfYear->setTime(0, 0);

    //get ventes id
    $eventVentesId = $this->eventTypeRepository->findOneBy(['name' => 'VENTES'])->getId();

    // Query for total stock quantity and values, filtering by company
    $stockQuery = $this->itemRepository->createQueryBuilder('i')
        ->select('SUM(i.quantity) as totalQuantity, SUM(i.buyPrice * i.quantity) as totalBuyValue, SUM(i.sellPrice * i.quantity) as totalSellValue')
        ->where('i.company = :companyId')
        ->setParameter('companyId', $companyId)
        ->getQuery()
        ->getSingleResult();

    // Events today including count
    $eventsTodayQuery = $this->eventRepository->createQueryBuilder('e')
        ->select('COUNT(e.id) as eventCount, SUM(ei.quantity) as quantity, SUM(ei.price * ei.quantity) as amount')
        ->leftJoin('e.eventItems', 'ei')
        ->where('e.eventType = :typeId AND e.eventDate >= :today AND e.company = :companyId')
        ->setParameter('typeId', $eventVentesId)
        ->setParameter('today', $today)
        ->setParameter('companyId', $companyId)
        ->getQuery()
        ->getSingleResult();

    // Events this month including count
    $eventsMonthQuery = $this->eventRepository->createQueryBuilder('e')
        ->select('COUNT(e.id) as eventCount, SUM(ei.quantity) as quantity, SUM(ei.price * ei.quantity) as amount')
        ->leftJoin('e.eventItems', 'ei')
        ->where('e.eventType = :typeId AND e.eventDate >= :startMonth AND e.company = :companyId')
        ->setParameter('typeId', $eventVentesId)
        ->setParameter('startMonth', $firstDayOfMonth)
        ->setParameter('companyId', $companyId)
        ->getQuery()
        ->getSingleResult();

    // Events this year including count
    $eventsYearQuery = $this->eventRepository->createQueryBuilder('e')
        ->select('COUNT(e.id) as eventCount, SUM(ei.quantity) as quantity, SUM(ei.price * ei.quantity) as amount')
        ->leftJoin('e.eventItems', 'ei')
        ->where('e.eventType = :typeId AND e.eventDate >= :startYear AND e.company = :companyId')
        ->setParameter('typeId', $eventVentesId)
        ->setParameter('startYear', $firstDayOfYear)
        ->setParameter('companyId', $companyId)
        ->getQuery()
        ->getSingleResult();

    // Prepare the dashboard data
    $dashboardData = [
        'StockToday' => [
            'TotalQuantity' => $stockQuery['totalQuantity'],
            'TotalValueBuy' => $stockQuery['totalBuyValue'],
            'TotalValueSell' => $stockQuery['totalSellValue']
        ],
        'EventsToday' => [
            'TotalSellQuantity' => $eventsTodayQuery['quantity'],
            'TotalSellAmount' => $eventsTodayQuery['amount'],
            'EventCount' => $eventsTodayQuery['eventCount']
        ],
        'EventsCurrentMonth' => [
            'TotalSellQuantity' => $eventsMonthQuery['quantity'],
            'TotalSellAmount' => $eventsMonthQuery['amount'],
            'EventCount' => $eventsMonthQuery['eventCount']
        ],
        'EventsCurrentYear' => [
            'TotalSellQuantity' => $eventsYearQuery['quantity'],
            'TotalSellAmount' => $eventsYearQuery['amount'],
            'EventCount' => $eventsYearQuery['eventCount']
        ]
    ];

    return $this->json($dashboardData);
}

}
