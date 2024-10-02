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
use App\Entity\Payment;
use App\Repository\PaymentTypeRepository;

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
        PaymentTypeRepository $paymentTypeRepository
    ) {
        $this->entityManager = $entityManager;
        $this->eventRepository = $eventRepository;
        $this->itemRepository = $itemRepository;
        $this->businessPartnerRepository = $businessPartnerRepository;
        $this->companyRepository = $companyRepository;
        $this->eventTypeRepository = $eventTypeRepository;
        $this->userRepository = $userRepository;
        $this->serializer = $serializer;
        $this->paymentTypeRepository = $paymentTypeRepository;
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
            } else if ($eventType->getIsAnIncreaseStockType() == 0) {
                $item->setQuantity($item->getQuantity() - $eventItem->getQuantity());
            }

            $this->entityManager->persist($eventItem);
        }

        // Update event with total quantities and prices
        $event->setTotalQuantity($totalQuantity);
        $event->setTotalPrice($totalPrice);

        // update totalPrice if tva rate was provided
        if (isset($data['tva']) && $data['tva'] != 0)  {
            $totalPrice = $event->getTotalPrice() + ($event->getTotalPrice() * $event->getTva()/100);
            $event->setTotalPrice($totalPrice);
        }


         // create payment if instant payment was set
         if (isset($data['is_instant_payment_done'])) {
            $is_instant_payment_done = $data['is_instant_payment_done'];
            if ($is_instant_payment_done === true) {
                //get ventes id
                $paymentType = $this->paymentTypeRepository->findOneBy(['name' => 'ESPECES']);
                $payment = new Payment();
                $payment->setPaymentDate(new \DateTime());
                $payment->setAmount($event->getTotalPrice());
                $payment->setUser($user);
                $payment->setPaymentType($paymentType);
                $payment->setEvent($event);
                $payment->setCreatedAt(new \DateTime()); 
                $event->setTotalPayment($event->getTotalPrice());
                $this->entityManager->persist($payment);
            }
        }

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

        // if the status is already cancelled not updated needed
        if (strtolower($event->getStatus()) === 'cancelled') {
            return $this->json(['message' => 'Invalid status'], JsonResponse::HTTP_BAD_REQUEST);
        }
        

        // Check if we are cancelling the event and handle rollback
        if (strtolower($data['status']) === 'cancelled') {
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
    $validated = 'validated'; 

    // Define the current month's start
    $firstDayOfMonth = new \DateTime('first day of this month');
    $firstDayOfMonth->setTime(0, 0);

    // Define the current year's start
    $firstDayOfYear = new \DateTime('first day of January this year');
    $firstDayOfYear->setTime(0, 0);

    $endToday = new \DateTime();
    $endToday->setTime(23, 59, 59);
    
    // Define the current month's start
    $endDayOfMonth = new \DateTime('last day of this month');
    $endDayOfMonth->setTime(23, 59, 59);

    // Define the current year's start
    $endDayOfYear = new \DateTime('last day of December this year');
    $endDayOfYear->setTime(23, 59, 59);
    $mock = [
        'endDayOfYear'=>$endDayOfYear,
        'lastDayOfMonth'=>$endDayOfMonth,
        'endToday'=>$endToday,
        'firstDayOfYear'=>$firstDayOfYear,
        'firstDayOfMonth'=>$firstDayOfMonth,
        'today'=>$today,
    ];

    // Here, call the getMonthlySales method directly and integrate its results
    $monthlySalesResponse = $this->getMonthlySales($companyId);
    $monthlySalesData = json_decode($monthlySalesResponse->getContent(), true);

    //get ventes id
    $eventVentesId = $this->eventTypeRepository->findOneBy(['name' => 'VENTES'])->getId();

    // Query for total stock quantity and values, filtering by company
    $stockQuery = $this->itemRepository->createQueryBuilder('i')
        ->select('SUM(i.quantity) as totalQuantity, SUM(i.buyPrice * i.quantity) as totalBuyValue, SUM(i.sellPrice * i.quantity) as totalSellValue')
        ->where('i.company = :companyId')
        ->setParameter('companyId', $companyId)
        ->getQuery()
        ->getSingleResult();

    
    // Sum of payments today
    $paymentsToday = $this->getPaymentsSum($companyId, $today, $endToday, $eventVentesId);

    // Sum of payments this month
    $paymentsMonth = $this->getPaymentsSum($companyId, $firstDayOfMonth, $endDayOfMonth, $eventVentesId);

    // Sum of payments this year
    $paymentsYear = $this->getPaymentsSum($companyId, $firstDayOfYear, $endDayOfYear,$eventVentesId);


    // Events today including count
    $eventsTodayQuery = $this->eventRepository->createQueryBuilder('e')
        ->select('COUNT(distinct(e.id)) as eventCount,  SUM(DISTINCT e.totalPrice) as amount, SUM(ei.quantity) as quantity')
        ->leftJoin('e.eventItems', 'ei')
        ->where('e.eventType = :typeId AND e.company = :companyId')
        ->andWhere('e.eventDate BETWEEN :start AND :end')
        ->andWhere('e.status = :status')
        ->setParameter('start', $today)
        ->setParameter('end', $endToday)
        ->setParameter('typeId', $eventVentesId)
        ->setParameter('companyId', $companyId)
        ->setParameter('status', $validated)
        ->getQuery()
        ->getSingleResult();

    // Events this month including count
    $eventsMonthQuery = $this->eventRepository->createQueryBuilder('e')
        ->select('COUNT(distinct(e.id)) as eventCount, SUM(ei.quantity) as quantity, SUM(DISTINCT e.totalPrice) as amount')
        ->leftJoin('e.eventItems', 'ei')
        ->where('e.eventType = :typeId AND e.company = :companyId')
        ->andWhere('e.eventDate BETWEEN :start AND :end')
        ->andWhere('e.status = :status')
        ->setParameter('start', $firstDayOfMonth)
        ->setParameter('end', $endDayOfMonth)
        ->setParameter('typeId', $eventVentesId)
        ->setParameter('companyId', $companyId)
        ->setParameter('status', $validated)
        ->getQuery()
        ->getSingleResult();

    // Events this year including count
    $eventsYearQuery = $this->eventRepository->createQueryBuilder('e')
        ->select('COUNT(distinct(e.id)) as eventCount, SUM(ei.quantity) as quantity, SUM(DISTINCT e.totalPrice) as amount')
        ->leftJoin('e.eventItems', 'ei')
        ->where('e.eventType = :typeId AND e.company = :companyId')
        ->andWhere('e.eventDate BETWEEN :start AND :end')
        ->andWhere('e.status = :status')
        ->setParameter('start', $firstDayOfYear)
        ->setParameter('end', $endDayOfYear)
        ->setParameter('typeId', $eventVentesId)
        ->setParameter('companyId', $companyId)
        ->setParameter('status', $validated)
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
        ],
        'SellPayments' => [
            'TotalPaymentToday' => $paymentsToday['totalPayments'],
            'TotalPaymentMonth' => $paymentsMonth['totalPayments'],
            'TotalPaymentYear' => $paymentsYear['totalPayments'],
            'PaymentCount' => $paymentsYear['paymentCount']
        ],
        'MonthlySales' => $monthlySalesData, // Adding the new monthly sales data
    ];

    return $this->json($dashboardData);
}



private function getPaymentsSum(int $companyId, \DateTime $startDate, \DateTime $endDate, int $eventTypeId): array
{
    $validated = 'validated';

    $entityManager = $this->entityManager;  // Use the injected EntityManager

    $query = $entityManager->createQueryBuilder()
        ->select('COUNT(p.id) as paymentCount, SUM(p.amount) as totalPayments')
        ->from(Payment::class, 'p')
        ->join('p.event', 'e')
        ->where('e.company = :companyId AND p.paymentDate BETWEEN :start AND :end')
        ->andWhere('e.status = :status')
        ->andWhere('e.eventType = :eventType')  // Filter for event type name 'VENTES'
        ->setParameter('companyId', $companyId)
        ->setParameter('start', $startDate)
        ->setParameter('end', $endDate)
        ->setParameter('eventType',  $eventTypeId)  // Set the specific event type id
        ->setParameter('status', $validated)
        ->getQuery();

    $result = $query->getSingleResult();

    return [
        'totalPayments' => $result['totalPayments'] ?? 0,
        'paymentCount' => $result['paymentCount'] ?? 0
    ];
}

#[Route('/unpaid/{companyId}', methods: ['GET'])]
public function getUnpaidEvents(Request $request, int $companyId): JsonResponse
{
    $startDate = $request->query->get('startDate');
    $endDate = $request->query->get('endDate');
    $eventTypeName = $request->query->get('eventType');
    $partnerId = $request->query->get('partnerId');
   
    $validated = 'validated';
    
    // Convert dates from string to DateTime objects
    $startDate = new \DateTime($startDate);
    $endDate = new \DateTime($endDate);

    // Fetch the event type by name
    $eventType = $this->eventTypeRepository->findOneBy(['name' => $eventTypeName]);

    // Query for events that are unpaid, including business partner and event type names
    $query = $this->entityManager->createQueryBuilder()
        ->select([
            'e',
            'SUM(p.amount) AS totalPaid',
            'bp.name AS businessPartnerName', // Add business partner name
            'et.name AS eventTypeName'        // Add event type name
        ])
        ->from(Event::class, 'e')
        ->leftJoin('e.payments', 'p')
        ->leftJoin('e.businessPartner', 'bp')
        ->leftJoin('e.eventType', 'et')
        ->groupBy('e.id')
        ->having('SUM(p.amount) < e.totalPrice OR SUM(p.amount) IS NULL')
        ->where('e.company = :companyId')
        ->andWhere('e.eventDate BETWEEN :startDate AND :endDate')
        ->andWhere('e.status = :status')
        ->setParameter('status', $validated)
        ->setParameter('companyId', $companyId)
        ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate);
    
    // Optional filtering by partner
   
    // partner filter ain't working we comment it for now will fix later !
    // the report will work for all
    if ($partnerId) {
       $query->andWhere('e.businessPartner = :partnerId')
       ->setParameter('partnerId', $partnerId);
    }

    if ($eventType) {
        $query->andWhere('et = :eventType')
        ->setParameter('eventType', $eventType);
     }

    $unpaidEvents = $query->getQuery()->getResult();

    // Process results to include total paid amount and additional details
    $results = array_map(function ($result) {
        /** @var Event $event */
        $event = $result[0]; // This is the Event object
        return [
            'id' => $event->getId(),
            'eventDate' => $event->getEventDate()->format('Y-m-d H:i:s'),
            'totalPrice' => $event->getTotalPrice(),
            'totalPaid' => $result['totalPaid'] ?? 0,
            'status' => $event->getStatus(),
            'businessPartnerName' => $result['businessPartnerName'], // Add this line
            'eventTypeName' => $result['eventTypeName'], // Add this line
        ];
    }, $unpaidEvents);

    return $this->json($results, JsonResponse::HTTP_OK);
}


#[Route('/financial-summary/{companyId}', methods: ['GET'])]
public function getFinancialSummaryByEventType(Request $request, int $companyId): JsonResponse
{
    $startDate = $request->query->get('startDate');
    $endDate = $request->query->get('endDate');
    $partnerId = $request->query->get('partnerId');

    $startDate = new \DateTime($startDate);
    $endDate = new \DateTime($endDate);
    
    $validated = 'validated';

    // Start building the query
    $qb = $this->entityManager->createQueryBuilder();

    // Select and sum based on conditions for 'VENTES' and 'ACHATS'
    $qb->select([
        'SUM(CASE WHEN et.name = \'VENTES\' THEN e.totalPrice ELSE 0 END) AS sumVentes',
        'SUM(CASE WHEN et.name = \'ACHATS\' THEN e.totalPrice ELSE 0 END) AS sumAchats',
        'SUM(CASE WHEN et.name = \'VENTES\' THEN p.amount ELSE 0 END) AS sumPaidVentes',
        'SUM(CASE WHEN et.name = \'ACHATS\' THEN p.amount ELSE 0 END) AS sumPaidAchats'
    ])
    ->from(Event::class, 'e')
    ->leftJoin('e.eventType', 'et')
    ->leftJoin('e.payments', 'p')
    ->where('e.company = :companyId')
    ->andWhere('e.eventDate BETWEEN :startDate AND :endDate')
    ->andWhere('e.status = :status')
    ->setParameter('status', $validated)
    ->setParameter('companyId', $companyId)
    ->setParameter('startDate', $startDate)
    ->setParameter('endDate', $endDate);

    // Optional filtering by partner
    if ($partnerId) {
        $qb->andWhere('e.businessPartner = :partnerId')
           ->setParameter('partnerId', $partnerId);
    }

    // Get the result
    $result = $qb->getQuery()->getSingleResult();

    // Calculate the difference between sumAchats and sumVentes
    $difference = $result['sumVentes'] - $result['sumAchats'];

    // Calculate the difference between sumAchats and sumVentes
    $paidDifference = $result['sumPaidVentes'] - $result['sumPaidAchats'];

    // Construct the response
    $data = [
        'sumVentes' => $result['sumVentes'],
        'sumAchats' => $result['sumAchats'],
        'difference' => $difference,
        'sumPaidVentes' => $result['sumPaidVentes'],
        'sumPaidAchats' => $result['sumPaidAchats'],
        'paidDifference' => $paidDifference,
    ];

    return $this->json($data);
}

#[Route('/api/events/monthly-sales/{companyId}', methods: ['GET'])]
public function getMonthlySales(int $companyId): JsonResponse
{
    $currentDate = new \DateTime();
    $endDate = new \DateTime('last day of this month');
    $startDate = (clone $currentDate)->modify('-11 months')->modify('first day of this month');

    $qb = $this->entityManager->createQueryBuilder();
    
    $validated = 'validated';

    $qb->select([
            'SUBSTRING(e.eventDate, 1, 7) AS monthYear', // YYYY-MM format
            'SUM(e.totalPrice) AS totalSales'
        ])
        ->from(Event::class, 'e')
        ->join('e.eventType', 'et')
        ->where('et.name = :typeName')
        ->andWhere('e.company = :companyId')
        ->andWhere('e.eventDate BETWEEN :startDate AND :endDate')
        ->andWhere('e.status = :status')
        ->groupBy('monthYear')
        ->orderBy('monthYear', 'ASC')
        ->setParameter('typeName', 'VENTES')
        ->setParameter('companyId', $companyId)
        ->setParameter('status', $validated)
        ->setParameter('startDate', $startDate->format('Y-m-d'))
        ->setParameter('endDate', $endDate->format('Y-m-d'));

    $results = $qb->getQuery()->getScalarResult();
    $formattedResults = $this->initializeMonthlyResults($startDate, 12);

    // Update the initialized array with actual results
    foreach ($results as $result) {
        $date = \DateTime::createFromFormat('Y-m', $result['monthYear']);
        $formattedMonth = $this->getFrenchMonthAbbreviation($date->format('m')); // Use a custom function to map month number to French abbreviation
        foreach ($formattedResults as &$monthlyResult) {
            if ($monthlyResult['month'] === $formattedMonth) {
                $monthlyResult['sales'] = (float) $result['totalSales'];
                break;
            }
        }
    }

    return $this->json($formattedResults);
}

private function initializeMonthlyResults(\DateTime $startDate, int $months = 12): array
{
    $results = [];
    $date = clone $startDate;

    for ($i = 0; $i < $months; $i++) {
        $results[] = [
            'month' => $this->getFrenchMonthAbbreviation($date->format('m')),
            'sales' => 0
        ];
        $date->modify('+1 month');
    }

    return $results;
}

private function getFrenchMonthAbbreviation($monthNumber): string
{
    $map = [
        '01' => 'Jan.',
        '02' => 'Fév.',
        '03' => 'Mar.',
        '04' => 'Avr.',
        '05' => 'Mai',
        '06' => 'Juin',
        '07' => 'Juil.',
        '08' => 'Aoû.',
        '09' => 'Sep.',
        '10' => 'Oct.',
        '11' => 'Nov.',
        '12' => 'Déc.'
    ];
    return $map[$monthNumber] ?? 'N/A';
}

#[Route('/ranking/{companyId}', methods: ['GET'])]
public function getProductRankingByAmount(Request $request, int $companyId): JsonResponse
{
    // Get startDate and endDate from request query parameters
    $startDate = $request->query->get('startDate');
    $endDate = $request->query->get('endDate');

    if (!$startDate || !$endDate) {
        return new JsonResponse(['status' => 0, 'message' => 'Both startDate and endDate are required'], JsonResponse::HTTP_BAD_REQUEST);
    }

    // Convert dates from string to DateTime objects
    $startDate = new \DateTime($startDate);
    $endDate = new \DateTime($endDate);

    // Fetch the 'VENTES' event type
    $ventesEventType = $this->eventTypeRepository->findOneBy(['name' => 'VENTES']);
    if (!$ventesEventType) {
        return new JsonResponse(['status' => 0, 'message' => 'Event type VENTES not found'], JsonResponse::HTTP_NOT_FOUND);
    }

    // Build the query
    $qb = $this->entityManager->createQueryBuilder();
    $qb->select([
            'i.name AS productName',
            'i.reference AS productReference',
            'SUM(ei.quantity * ei.price) AS totalSalesAmount',
            'SUM(ei.quantity) AS totalQuantitySold'
        ])
        ->from(EventItem::class, 'ei')  // Start from the EventItem entity
        ->innerJoin('ei.item', 'i')      // Join the Item entity
        ->innerJoin('ei.event', 'e')     // Join the Event entity
        ->where('e.eventType = :eventType')
        ->andWhere('e.company = :companyId')
        ->andWhere('e.eventDate BETWEEN :startDate AND :endDate')
        ->groupBy('i.id')  // Group by item to aggregate quantities and sales amounts
        ->orderBy('totalSalesAmount', 'DESC')  // Order by total sales amount
        ->setParameter('eventType', $ventesEventType)
        ->setParameter('companyId', $companyId)
        ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate);

    // Execute the query and get the result
    $ranking = $qb->getQuery()->getResult();

    // If no results found, return an empty array
    if (empty($ranking)) {
        return new JsonResponse(['status' => 1, 'message' => 'No items sold in the given period'], JsonResponse::HTTP_OK);
    }

    // Return the ranking as JSON response
    return $this->json($ranking);
}


}
