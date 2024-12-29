<?php

namespace App\Controller;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Repository\SubscriptionRepository;
use App\Entity\Subscription;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use App\Repository\EventRepository;
use App\Repository\ItemRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Item;
use App\Entity\EventItem;
use App\Entity\Event;
use App\Entity\Payment;

#[Route('/api/reports')]
class ReportController extends AbstractController
{
    private $entityManager;
    private $serializer;
    private ValidatorInterface $validator;
    private CompanyRepository $companyRepository;
    private SubscriptionRepository $subscriptionRepository;
    private EventRepository $eventRepository;
    private MailerInterface $mailer;
    private ItemRepository $itemRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        CompanyRepository $companyRepository,
        SubscriptionRepository $subscriptionRepository,
        MailerInterface $mailer,
        EventRepository $eventRepository,
        ItemRepository $itemRepository
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->companyRepository = $companyRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->eventRepository = $eventRepository;
        $this->mailer = $mailer;
        $this->itemRepository = $itemRepository;
    }

    #[Route('/remind-no-transaction', methods: ['POST'])]
    public function remindCompaniesNoTransaction(): JsonResponse
    {
        // Find companies that have not recorded any transaction/event
        $companies = $this->companyRepository->findAll();

        $companiesWithoutEvents = array_filter($companies, function ($company) {
            $events = $this->eventRepository->findBy(['company' => $company]);
            return count($events) === 0;
        });

        foreach ($companiesWithoutEvents as $company) {
            $this->sendReminderEmail($company);
        }

        return new JsonResponse([
            'status' => 1,
            'message' => 'Reminder emails have been sent to companies without transactions.',
            'count' => count($companiesWithoutEvents)
        ], JsonResponse::HTTP_OK);
    }

    private function sendReminderEmail(Company $company): void
    {
        /* Uncomment for testing
        $allowedEmails = ['adovene@gmail.com', 'dagogue@yahoo.fr'];
    
        // Check if the company's email is in the allowed list
        if (!in_array($company->getEmail(), $allowedEmails)) {
            return;
        }*/

        $playStoreUrl = 'https://play.google.com/store/apps/details?id=com.dov.shopique&pli=1';
        $appStoreUrl = 'https://apps.apple.com/us/app/shopiques/id6664070531';
        $webUrl = 'https://shopiques-app.inaxxe.com';

        $email = (new Email())
            ->from(new Address('office@inaxxe.com', 'Shopiques'))
            ->to($company->getEmail())
            ->subject('Boostez votre entreprise avec Shopiques!')
            ->html(
                "<p>Bonjour " . $company->getName() . ",</p>" .
                    "<p>Nous avons remarqué que vous n'avez pas encore enregistré de transaction dans votre application Shopiques.<br>Enregistrez vos transactions pour tirer pleinement parti de la puissance de votre application afin de maîtriser votre commerce et booster votre entreprise.</p>" .
                    "<p>Le guide utilisateur est disponible <a href='https://shopiques.inaxxe.com/#user-guide'>ici</a> pour vous accompagner dans vos premières saisies d'articles et de transactions.</p>" .
                    "<p>Relancez votre application: <br>" .
                    "<a href='$playStoreUrl'>Android</a> | <a href='$appStoreUrl'>iOS</a> | <a href='$webUrl'>Web mobile</a></p>" .
                    "<p>Si vous avez des questions ou avez besoin d'aide, n'hésitez pas à nous contacter au +33660506626 (utiliser de préférence WhatsApp) ou par mail à <a href='mailto:office@inaxxe.com'>office@inaxxe.com</a>.</p>" .
                    "<p>Cordialement,<br><br>L'équipe Shopiques.</p>"
            );

        // Send the reminder email
        $this->mailer->send($email);
    }

    #[Route('/remind-3days-no-event', methods: ['POST'])]
    public function remindCompaniesNoRecentEvent(): JsonResponse
    {
        // 1. Determine the date threshold for 3 days
        $threshold = new \DateTime('-3 days');

        // 2. Get all companies
        $allCompanies = $this->companyRepository->findAll();

        // 3. Filter to find those who have not recorded any event in the last 3 days
        $companiesNoRecentEvent = array_filter($allCompanies, function (Company $company) use ($threshold) {
            // Query for events created in the last 3 days
            $recentEvents = $this->eventRepository->createQueryBuilder('e')
                ->where('e.company = :company')
                ->andWhere('e.eventDate >= :threshold')
                ->setParameter('company', $company)
                ->setParameter('threshold', $threshold)
                ->getQuery()
                ->getResult();

            // If no recent events found, this company qualifies
            return count($recentEvents) === 0;
        });

        // 4. Send a reminder email to each company that hasn't recorded an event in last 3 days
        foreach ($companiesNoRecentEvent as $company) {
            $this->sendNoTransaction3DaysEmail($company);
        }

        // 5. Return a JSON response with count
        return new JsonResponse([
            'status' => 1,
            'message' => 'Reminder emails have been sent to companies without events in the last 3 days.',
            'count' => count($companiesNoRecentEvent)
        ]);
    }

    // A new method to send the email for companies with no recent event
    private function sendNoTransaction3DaysEmail(Company $company): void
    {
        $playStoreUrl = 'https://play.google.com/store/apps/details?id=com.dov.shopique&pli=1';
        $appStoreUrl = 'https://apps.apple.com/us/app/shopiques/id6664070531';
        $webUrl = 'https://shopiques-app.inaxxe.com';

        $email = (new Email())
            ->from(new Address('office@inaxxe.com', 'Shopiques'))
            ->to($company->getEmail())
            ->subject('Aucun événement saisi depuis 3 jours ?')
            ->html(
                "<p>Bonjour " . $company->getName() . ",</p>" .
                    "<p>Nous avons remarqué qu'aucune transaction (ventes, achats etc.) n'a été enregistrée dans votre application Shopiques au cours des trois derniers jours. Saisir régulièrement vos transactions est un moyen efficace 
                     pour mieux gérer votre commerce et booster votre activité.</p>" .
                    "<p>Si vous avez des questions ou avez besoin d'aide, n'hésitez pas à nous contacter au +33660506626 (utiliser de préférence WhatsApp) ou par mail à <a href='mailto:office@inaxxe.com'>office@inaxxe.com</a>.</p>" .
                    "<p>Nous sommes là pour vous aider à tirer le meilleur parti de l'application Shopiques.</p>" .
                    "<p>Le guide utilisateur est disponible <a href='https://shopiques.inaxxe.com/#user-guide'>ici</a> pour vous accompagner dans vos saisies de transactions ou d'articles.</p>" .
                    "<p>Relancez votre application: <br>" .
                    "<a href='$playStoreUrl'>Android</a> | <a href='$appStoreUrl'>iOS</a> | <a href='$webUrl'>Web mobile</a></p>" .
                    "<p>Cordialement,<br><br>L'équipe Shopiques.</p>"
            );

        $this->mailer->send($email);
    }

    #[Route('/reminder/auto-remind-3days-no-event', methods: ['GET'])]
    public function autoRemindCompaniesNoRecentEvent(): JsonResponse
    {
        // 1. Determine the date threshold for 3 days
        $threshold = new \DateTime('-3 days');

        // 2. Get all companies
        $allCompanies = $this->companyRepository->findAll();

        // 3. Filter to find those who have not recorded any event in the last 3 days
        $companiesNoRecentEvent = array_filter($allCompanies, function (Company $company) use ($threshold) {
            // Query for events created in the last 3 days
            $recentEvents = $this->eventRepository->createQueryBuilder('e')
                ->where('e.company = :company')
                ->andWhere('e.eventDate >= :threshold')
                ->setParameter('company', $company)
                ->setParameter('threshold', $threshold)
                ->getQuery()
                ->getResult();

            // If no recent events found, this company qualifies
            return count($recentEvents) === 0;
        });

        // 4. Send a reminder email to each company that hasn't recorded an event in last 3 days
        foreach ($companiesNoRecentEvent as $company) {
            $this->sendNoTransaction3DaysEmail($company);
        }

        // 5. Return a JSON response with count
        return new JsonResponse([
            'status' => 1,
            'message' => 'Reminder emails have been sent to companies without events in the last 3 days.',
            'count' => count($companiesNoRecentEvent)
        ]);
    }

    #[Route('/all-companies-message', methods: ['POST'])]
    public function messageToAllCompanies(): JsonResponse
    {
        // 1. Get all companies
        $allCompanies = $this->companyRepository->findBy(['id' => 7]);

        // 2. Send the holiday greeting email to each
        foreach ($allCompanies as $company) {
            $this->sendMessageToAllCompanies($company);
        }

        // 3. Return a JSON response
        return new JsonResponse([
            'status' => 1,
            'message' => 'Message emails have been sent to all companies.',
            'count' => count($allCompanies),
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Send a “Message” email to the specified company.
     */
    private function sendMessageToAllCompanies(Company $company): void
    {
        $email = (new Email())
            ->from(new Address('office@inaxxe.com', 'Shopiques'))
            ->to($company->getEmail())
            ->subject("Joyeuses fêtes de fin d'année")
            ->html(
                "<p><strong>Bonjour {$company->getName()},</strong></p>" .
                    "<p>L'équipe Shopiques vous souhaite de très belles fêtes de fin d’année, <strong>Joyeux Noël</strong> et nos meilleurs vœux pour une <strong>excellente année 2025</strong> !</p>" .
                    "<p>Que ces fêtes soient l'occasion de se reposer, de passer d'agréables moments en famille et de préparer au mieux la nouvelle année.</p>" .
                    "<p>Nous préparons des <strong>fonctionnalités passionnantes</strong> pour l'année à venir, qui vous aideront à <strong>booster votre commerce</strong> et vous faciliteront la vie.</p>" .
                    "<p>Chut, un petit spoiler : bientôt, vous pourrez recevoir périodiquement un <strong>relevé de votre situation financière et de votre stock</strong> par email — un peu comme un relevé bancaire :) </p>" .
                    "<p><strong>Nous restons à votre disposition</strong> si vous avez des questions ou si vous avez besoin d'aide dans l'utilisation de notre application.</p>" .
                    "<p>Cordialement,<br><strong>L'équipe Shopiques</strong>.</p>"
            );

        $this->mailer->send($email);
    }


    /**
     * add a route that send to all companies an email to report the stock situation. In attached file add the list of the company items and stock quantity
     **/

    #[Route('/stock-periodic-report', methods: ['POST'])]
    public function sendStockPeriodicReport(Request $request): JsonResponse
    {
        // You can read a 'period' parameter if needed, default to monthly
        $period = $request->query->get('period', 'monthly');

        // Calculate start/end
        [$startDate, $endDate] = $this->calculatePeriodDates($period);

        // For test: Only company with ID=7
        // For production: $companies = $this->companyRepository->findAll();
        $companies = $this->companyRepository->findBy(['id' => 7]);

        foreach ($companies as $company) {
            $items = $this->itemRepository->findBy(['company' => $company]);

            // Build PDF
            $pdfContent = $this->buildStockPdf($company, $items, $startDate, $endDate);

            // Email
            $email = (new Email())
                ->from(new Address('office@inaxxe.com', 'Shopiques'))
                ->to($company->getEmail())
                ->subject("Rapport de stock - {$period}")
                ->text("Veuillez trouver ci-joint votre rapport de stock pour la période {$period}.")
                ->attach($pdfContent, "stock_report_{$period}.pdf", 'application/pdf');

            $this->mailer->send($email);
        }

        return new JsonResponse([
            'status' => 1,
            'message' => "Stock report ($period) emails sent to all companies.",
            'count' => count($companies),
        ]);
    }

    private function calculatePeriodDates(string $period): array
    {
        // End date up to the last second of the day
        $endDate = new \DateTime();
        $endDate->setTime(23, 59, 59);

        if ($period === 'weekly') {
            // 7 days ago, start from 00:00:00
            $startDate = new \DateTime();
            $startDate->modify('-7 days');
            $startDate->setTime(0, 0, 0);
        } else {
            // "Monthly" case: first day of this month, starting at 00:00:00
            $startDate = new \DateTime('first day of this month');
            $startDate->setTime(0, 0, 0);
        }

        return [$startDate, $endDate];
    }

    private function buildStockPdf(Company $company, array $items, \DateTime $startDate, \DateTime $endDate): string
    {
        $html = $this->renderView('pdf/stock_report.html.twig', [
            'company'   => $company,
            'itemsData' => $this->computeStockData($items, $startDate, $endDate),
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function computeStockData(array $items, \DateTime $startDate, \DateTime $endDate): array
    {
        $results = [];

        foreach ($items as $item) {

            // if item does not requires stock management, skip it
            if (!$item->getRequiresStockManagement()) {
                continue;
            }
            //$finalQty = $item->getQuantity();

            $initialQty = $this->calculateInitialQuantity($item, $startDate);

            $qtyIn  = $this->calculateQuantityIn($item, $startDate, $endDate);
            $qtyOut = $this->calculateQuantityOut($item, $startDate, $endDate);

            // final = initial + in - out => initial = final - in + out
            $finalQty = $initialQty + $qtyIn - $qtyOut;


            $results[] = [
                'name'       => $item->getName(),
                'reference'  => $item->getReference(),
                'initialQty' => $initialQty,
                'qtyIn'      => $qtyIn,
                'qtyOut'     => $qtyOut,
                'finalQty'   => $finalQty,
            ];
        }

        return $results;
    }


    private function calculateInitialQuantity(Item $item, \DateTime $startDate): int
    {
        // Basic approach:
        // 1. Find quantity at item creation or from a baseline field
        // 2. Add all "in" events up to $startDate
        // 3. Subtract all "out" events up to $startDate

        // Possibly you track an "initialQuantity" in the item entity itself:
        $base = $item->getInitialQuantity() ?? 0;

        // Then gather all events for that item before $startDate
        // If you store "isAnIncreaseStockType" or "event_type_id = 'ACHAT'" or "VENTES"
        // do a custom query to sum them up. Pseudocode:

        $validated = 'validated';

        $qb = $this->entityManager->createQueryBuilder()
            ->select('ei')
            ->from(EventItem::class, 'ei')
            ->join('ei.event', 'ev')
            ->where('ei.item = :item')
            ->andWhere('ev.eventDate < :startDate')
            ->andWhere('ev.status = :status')
            ->setParameter('item', $item)
            ->setParameter('startDate', $startDate)
            ->setParameter('status', $validated);

        $eventItemsBefore = $qb->getQuery()->getResult();

        $in = 0;
        $out = 0;
        foreach ($eventItemsBefore as $ei) {
            /** @var Event $ev */
            $ev = $ei->getEvent();
            if ($ev->getEventType()->getIsAnIncreaseStockType()) {
                $in += $ei->getQuantity();
            } else {
                $out += $ei->getQuantity();
            }
        }

        return $base + $in - $out;
    }



    private function calculateQuantityIn(Item $item, \DateTime $startDate, \DateTime $endDate): int
    {
        $validated = 'validated';

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COALESCE(SUM(ei.quantity), 0) as totalIn')
            ->from(EventItem::class, 'ei')
            ->join('ei.event', 'ev')
            ->where('ei.item = :item')
            ->andWhere('ev.eventDate BETWEEN :startDate AND :endDate')
            ->andWhere('ev.status = :status')
            ->andWhere('ev.eventType IN (
             SELECT et
             FROM App\Entity\EventType et
             WHERE et.isAnIncreaseStockType = 1
       )')
            ->setParameter('item', $item)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('status', $validated);

        $result = $qb->getQuery()->getOneOrNullResult();
        return $result['totalIn'] ?? 0;
    }

    private function calculateQuantityOut(Item $item, \DateTime $startDate, \DateTime $endDate): int
    {
        $validated = 'validated';
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COALESCE(SUM(ei.quantity), 0) as totalOut')
            ->from(EventItem::class, 'ei')
            ->join('ei.event', 'ev')
            ->where('ei.item = :item')
            ->andWhere('ev.eventDate BETWEEN :startDate AND :endDate')
            ->andWhere('ev.status = :status')
            ->andWhere('ev.eventType IN (
             SELECT et
             FROM App\Entity\EventType et
             WHERE et.isAnIncreaseStockType = 0
       )')
            ->setParameter('item', $item)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('status', $validated);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result['totalOut'] ?? 0;
    }


/**
 * add a route that send to all companies an email to report the events situation. 
 * In attached file add the list of the company events and their total amount
 **/

#[Route('/event-periodic-report', methods: ['POST'])]
public function sendEventPeriodicReport(Request $request): JsonResponse
{
    // 1. Determine the period (weekly or monthly). Default to 'monthly'.
    $period = $request->query->get('period', 'monthly');

    // 2. Calculate date range
    [$startDate, $endDate] = $this->calculatePeriodDates($period);

    // 3. For testing, you might fetch only company ID=7
    // Otherwise, fetch all companies: $companies = $this->companyRepository->findAll();
    $companies = $this->companyRepository->findBy(['id' => 7]);

    foreach ($companies as $company) {
        // 4. Build PDF for each company
        $pdfContent = $this->buildEventPdf($company, $startDate, $endDate);

        // 5. Compose the email
        $email = (new Email())
            ->from(new Address('office@inaxxe.com', 'Shopiques'))
            ->to($company->getEmail())
            ->subject("Rapport des transactions - {$period}")
            ->text("Veuillez trouver ci-joint votre rapport des événements pour la période {$period}.")
            ->attach($pdfContent, "events_report_{$period}.pdf", 'application/pdf');

        // 6. Send email
        $this->mailer->send($email);
    }

    return new JsonResponse([
        'status' => 1,
        'message' => "Event report ($period) sent to all companies.",
        'count' => count($companies),
    ], JsonResponse::HTTP_OK);
}

private function buildEventPdf(Company $company, \DateTime $startDate, \DateTime $endDate): string
{
    // 1. Fetch the relevant events for this company and date range
    $eventsData = $this->fetchEventData($company, $startDate, $endDate);

    // 2. Render the HTML via Twig
    $html = $this->renderView('pdf/events_report.html.twig', [
        'company'   => $company,
        'startDate' => $startDate,
        'endDate'   => $endDate,
        'eventsData' => $eventsData,
    ]);

    // 3. Use Dompdf to generate PDF
    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');  // or other suitable font
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // 4. Return the PDF as binary
    return $dompdf->output();
}

private function fetchEventData(Company $company, \DateTime $startDate, \DateTime $endDate): array
{
    $validated = 'validated';
    // 1. Query for events (with event type isFree=0) in the date range
    $qb = $this->entityManager->createQueryBuilder()
        ->select('e')
        ->from(Event::class, 'e')
        ->join('e.eventType', 'et')
        ->where('e.company = :company')
        ->andWhere('e.eventDate BETWEEN :startDate AND :endDate')
        ->andWhere('e.status = :status')
        ->andWhere('et.isFree = 0')
        ->orderBy('e.eventDate', 'ASC')
        ->setParameter('company', $company)
        ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate)
        ->setParameter('status', $validated);

    /** @var Event[] $events */
    $events = $qb->getQuery()->getResult();

    $results = [];
    $totalExpenses = 0;
    $totalIncome   = 0;

    foreach ($events as $event) {
        $eventDate = $event->getEventDate();
        $eventType = $event->getEventType();
        $dateString = $eventDate?->format('d/m/Y') ?? '';
        $transactionLabel = $this->makeTransactionLabel($event); // e.g. "achat N°1" or "vente N°2"

        // Decide if it's "expenses" or "income"
        // If isAnIncreaseStockType() == 1 => 'expenses'
        // If isAnIncreaseStockType() == 0 => 'income'
        $amountExpenses = null;
        $amountIncome   = null;
        $amount = $event->getTotalPrice() ?? 0;  // or whichever field holds the event's amount

        if ($eventType->getIsAnIncreaseStockType() == 1) {
            $amountExpenses = $amount;
            $totalExpenses += $amount;
        } else {
            $amountIncome = $amount;
            $totalIncome += $amount;
        }

        $results[] = [
            'date'         => $dateString,
            'transaction'  => $transactionLabel,
            'expenses'     => $amountExpenses,
            'income'       => $amountIncome,
        ];
    }

    // 2. At the end, add lines for totals and difference
    //    or pass them separately to twig
    $results[] = [
        'date'        => '',
        'transaction' => '<strong>Total</strong>',
        'expenses'    => $totalExpenses,
        'income'      => $totalIncome,
    ];
    $diff = $totalIncome - $totalExpenses;
    $results[] = [
        'date'        => '',
        'transaction' => '<strong>Différence (Recettes - Dépenses)</strong>',
        'expenses'    => '',
        'income'      => $diff,
    ];

    return $results;
}

private function makeTransactionLabel(Event $event): string
{
    $eventTypeName = strtolower($event->getEventType()->getName()); // e.g. "ACHAT" => "achat"
    return "{$eventTypeName} N°{$event->getId()}";
}


/**
 * add a route that send to all companies an email to report the payments situation. 
 * In attached file add the list of the company subscriptions and their status
 **/

#[Route('/payment-periodic-report', methods: ['POST'])]
public function sendPaymentPeriodicReport(Request $request): JsonResponse
{
    // 1. Determine the period (weekly or monthly). Default to 'monthly'.
    $period = $request->query->get('period', 'monthly');

    // 2. Calculate date range
    [$startDate, $endDate] = $this->calculatePeriodDates($period);

    // 3. For testing, you might fetch only company ID=7
    // Otherwise, fetch all companies: $companies = $this->companyRepository->findAll();
    $companies = $this->companyRepository->findBy(['id' => 7]);

    foreach ($companies as $company) {
        // 4. Build PDF for each company
        $pdfContent = $this->buildPaymentPdf($company, $startDate, $endDate);

        // 5. Compose the email
        $email = (new Email())
            ->from(new Address('office@inaxxe.com', 'Shopiques'))
            ->to($company->getEmail())
            ->subject("Rapport des paiements - {$period}")
            ->text("Veuillez trouver ci-joint votre rapport des paiements pour la période {$period}.")
            ->attach($pdfContent, "payments_report_{$period}.pdf", 'application/pdf');

        // 6. Send email
        $this->mailer->send($email);
    }

    return new JsonResponse([
        'status' => 1,
        'message' => "Payment report ($period) sent to all companies.",
        'count' => count($companies),
    ], JsonResponse::HTTP_OK);
}


private function buildPaymentPdf(Company $company, \DateTime $startDate, \DateTime $endDate): string
{
    // 1. Fetch the relevant payments for this company and date range
    $paymentsData = $this->fetchPaymentData($company, $startDate, $endDate);

    // 2. Render the HTML via Twig
    $html = $this->renderView('pdf/payments_report.html.twig', [
        'company'    => $company,
        'startDate'  => $startDate,
        'endDate'    => $endDate,
        'paymentsData' => $paymentsData,
    ]);

    // 3. Use Dompdf to generate PDF
    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');  // or another suitable font
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // 4. Return the PDF as a binary string
    return $dompdf->output();
}

private function fetchPaymentData(Company $company, \DateTime $startDate, \DateTime $endDate): array
{

    $validated = 'validated';

    // 1. Query for payments in the date range
    // Payment always has an event (mandatory), so no need to check p.event IS NOT NULL.
    $qb = $this->entityManager->createQueryBuilder()
        ->select('p')
        ->from(Payment::class, 'p')
        ->join('p.event', 'ev')
        ->join('ev.eventType', 'et')
        ->where('p.paymentDate BETWEEN :startDate AND :endDate')
        ->andWhere('ev.company = :company')
        ->andWhere('ev.status = :status')
        ->orderBy('p.paymentDate', 'ASC')
        ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate)
        ->setParameter('company', $company)
        ->setParameter('status', $validated);

    /** @var Payment[] $payments */
    $payments = $qb->getQuery()->getResult();

    $results = [];
    $totalExpenses = 0;
    $totalIncome   = 0;

    foreach ($payments as $payment) {
        // Payment date
        $paymentDate = $payment->getPaymentDate();
        $dateString  = $paymentDate?->format('d/m/Y') ?? '';

        // Payment number
        $paymentLabel = "Règlement N°{$payment->getId()}";

        // Retrieve the related event
        $event       = $payment->getEvent();
        $eventType   = $event->getEventType();
        $eventDate   = $event->getEventDate();
        $eventDateStr = $eventDate?->format('d/m/Y') ?? '';
        $eventLabel  = "{$eventType->getName()} N°{$event->getId()} du {$eventDateStr}";

        // Classify payment amount as expenses or income
        $amountExpenses = null;
        $amountIncome   = null;
        $amount = $payment->getAmount() ?? 0;

        // If isAnIncreaseStockType() == 1 => 'expenses'
        // If isAnIncreaseStockType() == 0 => 'income'
        if ($eventType->getIsAnIncreaseStockType() == 1) {
            $amountExpenses = $amount;
            $totalExpenses += $amount;
        } else {
            $amountIncome = $amount;
            $totalIncome += $amount;
        }

        $results[] = [
            'paymentDate'      => $dateString,
            'paymentNumber'    => $paymentLabel,
            'transactionLabel' => $eventLabel,
            'expenses'         => $amountExpenses,
            'income'           => $amountIncome,
        ];
    }

    // 2. Append totals and difference rows
    $results[] = [
        'paymentDate'      => '',
        'paymentNumber'    => '<strong>Total</strong>',
        'transactionLabel' => '',
        'expenses'         => $totalExpenses,
        'income'           => $totalIncome,
    ];

    $diff = $totalIncome - $totalExpenses;
    $results[] = [
        'paymentDate'      => '',
        'paymentNumber'    => '<strong>Différence (Recettes - Dépenses)</strong>',
        'transactionLabel' => '',
        'expenses'         => '',
        'income'           => $diff,
    ];

    return $results;
}


/**
 * add a route that send to all companies an email to report the stock, events and payments situation. 
 * In attached file add the list of the company items and stock quantity, the list of the company events and their total amount, 
 * the list of the company subscriptions and their status
 **/
#[Route('/all-periodic-reports', methods: ['POST'])]
public function sendAllPeriodicReports(Request $request): JsonResponse
{
    // 1. Determine the period (weekly or monthly). Default to 'monthly'
    $period = $request->query->get('period', 'monthly');

    // We'll convert 'weekly'/'monthly' to a French label for the email text
    $periodLabel = $period === 'weekly' ? 'hebdomadaire' : 'mensuelle';

    // 2. Calculate date range using your existing method
    [$startDate, $endDate] = $this->calculatePeriodDates($period);

    // 3. For testing, fetch only company ID=7 => $companies = $this->companyRepository->findBy(['id' => 7]);
    // In production, you'd likely do: $companies = $this->companyRepository->findAll();
    $companies = $this->companyRepository->findAll();

    foreach ($companies as $company) {
        // 4. Build each PDF
        // STOCK
        $items = $this->itemRepository->findBy(['company' => $company]);
        $stockPdf   = $this->buildStockPdf($company, $items, $startDate, $endDate);

        // EVENTS (transactions payantes)
        $eventPdf   = $this->buildEventPdf($company, $startDate, $endDate);

        // PAYMENTS (règlements)
        $paymentPdf = $this->buildPaymentPdf($company, $startDate, $endDate);

        // 5. Compose *one* email with 3 attachments
        $emailBody = 
            "Bonjour {$company->getName()},\n\n" .
            "Veuillez trouver ci-joint les 3 rapports de votre activité {$periodLabel} :\n" .
            "- Le relevé de stock\n" .
            "- Le relevé des transactions payantes\n" .
            "- Le relevé des règlements\n\n" .
            "Cordialement,\n" .
            "L'équipe Shopiques.";

        $email = (new Email())
            ->from(new Address('office@inaxxe.com', 'Shopiques'))
            ->to($company->getEmail())
            ->subject("Vos rapports d'activité {$periodLabel}")
            ->text($emailBody)
            // Attach the three PDFs
            ->attach($stockPdf,   "stock_report_{$period}.pdf",   'application/pdf')
            ->attach($eventPdf,   "events_report_{$period}.pdf",  'application/pdf')
            ->attach($paymentPdf, "payments_report_{$period}.pdf",'application/pdf');

        // 6. Send the single email
        $this->mailer->send($email);
    }

    return new JsonResponse([
        'status' => 1,
        'message' => "All 3 reports ($period) sent to each company in a single email.",
        'count' => count($companies),
    ], JsonResponse::HTTP_OK);
}


#[Route('/auto-monthly-reports', methods: ['GET'])]
public function triggerMonthlyReports(): JsonResponse
{
    $request = new Request();
    $request->query->set('period', 'monthly');
    return $this->sendAllPeriodicReports($request);
}

#[Route('/active-companies', methods: ['GET'])]
public function getActiveCompanies(Request $request): JsonResponse
{
    // Get number of weeks from query parameter, default to 2 if not provided
    $weeks = $request->query->get('weeks', 2);
    
    // Validate that weeks is a positive number
    if (!is_numeric($weeks) || $weeks <= 0) {
        return new JsonResponse([
            'status' => 0,
            'message' => 'The weeks parameter must be a positive number',
        ], JsonResponse::HTTP_BAD_REQUEST);
    }

    // Calculate the date threshold based on the number of weeks
    $threshold = new \DateTime("-{$weeks} weeks");
    $threshold->setTime(0, 0, 0);  // Start of the day

    // Get all companies
    $companies = $this->companyRepository->findAll();

    // Filter for active companies
    $activeCompanies = array_filter($companies, function (Company $company) use ($threshold) {
        $validated = 'validated';
        
        // Query for any events since the threshold date
        $recentEvents = $this->eventRepository->createQueryBuilder('e')
            ->where('e.company = :company')
            ->andWhere('e.eventDate >= :threshold')
            ->andWhere('e.status = :status')
            ->setParameter('company', $company)
            ->setParameter('threshold', $threshold)
            ->setParameter('status', $validated)
            ->setMaxResults(1)  // We only need to know if any exist
            ->getQuery()
            ->getResult();

        return count($recentEvents) > 0;
    });

    // Prepare response data
    $responseData = array_map(function (Company $company) {
        return [
            'id' => $company->getId(),
            'name' => $company->getName(),
            'email' => $company->getEmail(),
            // Add any other relevant company fields you want to include
        ];
    }, array_values($activeCompanies));

    return new JsonResponse([
        'status' => 1,
        'message' => "Active companies (past {$weeks} weeks) retrieved successfully",
        'count' => count($activeCompanies),
        'weeks' => (int)$weeks,
        'startDate' => $threshold->format('Y-m-d'),
        'companies' => $responseData
    ]);
}

}
