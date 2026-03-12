<?php

namespace App\Services;

use App\Entity\Medication;
use App\Repository\MedicationRepository;
use App\Request\MedicationSearchRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\File;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MedicationService
{
    public function __construct(
        private readonly MedicationRepository $medicationRepository,
        private readonly ResponsesService $responsesService,
        private readonly SerializerInterface $serializer,
        private readonly EntityHelperService $entityHelper,
        private readonly EntityManagerInterface $em,
        private readonly BdpmEnricherService $bdpm,
        private readonly HttpClientInterface $httpClient,
        private readonly UploadFileService $uploadFileService,
        private readonly PaginationService $paginationService,
    ) {}

    public function getAllMedications(?MedicationSearchRequest $searchRequest = null): JsonResponse
    {
        $queryBuilder = $this->medicationRepository->createQueryBuilder('m')
            ->where('m.isActive = :active')
            ->setParameter('active', true);
        
        if ($searchRequest) {
            $this->applyMedicationFilters($queryBuilder, $searchRequest);
            return $this->paginationService->paginate($queryBuilder, $searchRequest->page, $searchRequest->limit, ["medication"]);
        }
        
        return $this->paginationService->paginate($queryBuilder, 1, 10, ["medication"]);
    }

    private function applyMedicationFilters($queryBuilder, MedicationSearchRequest $searchRequest): void
    {
        if ($searchRequest->name) {
            $queryBuilder->andWhere('m.name LIKE :name')
                ->setParameter('name', '%' . $searchRequest->name . '%');
        }
        
        if ($searchRequest->category) {
            $queryBuilder->andWhere('m.category = :category')
                ->setParameter('category', $searchRequest->category);
        }
        
        if ($searchRequest->requiresPrescription !== null) {
            $queryBuilder->andWhere('m.requiresPrescription = :requiresPrescription')
                ->setParameter('requiresPrescription', $searchRequest->requiresPrescription);
        }
        
        if ($searchRequest->isReimbursed !== null) {
            $queryBuilder->andWhere('m.isReimbursed = :isReimbursed')
                ->setParameter('isReimbursed', $searchRequest->isReimbursed);
        }
        
        if ($searchRequest->minPrice) {
            $queryBuilder->andWhere('m.price >= :minPrice')
                ->setParameter('minPrice', $searchRequest->minPrice);
        }
        
        if ($searchRequest->maxPrice) {
            $queryBuilder->andWhere('m.price <= :maxPrice')
                ->setParameter('maxPrice', $searchRequest->maxPrice);
        }
        
        if ($searchRequest->manufacturer) {
            $queryBuilder->andWhere('m.manufacturer = :manufacturer')
                ->setParameter('manufacturer', $searchRequest->manufacturer);
        }
        
        if ($searchRequest->search) {
            $queryBuilder->andWhere('m.name LIKE :search OR m.description LIKE :search OR m.activeIngredient LIKE :search')
                ->setParameter('search', '%' . $searchRequest->search . '%');
        }
    }

    public function getMedicationById(string $id): JsonResponse
    {
        $medication = $this->medicationRepository->find(Uuid::fromString($id));
        
        if (!$medication) {
            return $this->responsesService->errorResponse("Médicament introuvable");
        }
        
        $body = json_decode($this->serializer->serialize($medication, 'json', ["groups" => "medication"]), true);
        
        if ($body === null) {
            return $this->responsesService->errorResponse("Erreur de sérialisation");
        }
        
        return $this->responsesService->successResponse($body, "Médicament trouvé");
    }

    public function getMedicationsByCategory(string $category, int $page = 1, int $limit = 10): JsonResponse
    {
        $queryBuilder = $this->medicationRepository->createQueryBuilder('m')
            ->where('m.category = :category')
            ->andWhere('m.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true);
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ["medication"]);
    }

    public function getPrescriptionMedications(int $page = 1, int $limit = 10): JsonResponse
    {
        $queryBuilder = $this->medicationRepository->createQueryBuilder('m')
            ->where('m.requiresPrescription = :prescription')
            ->andWhere('m.isActive = :active')
            ->setParameter('prescription', true)
            ->setParameter('active', true);
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ["medication"]);
    }

    public function getOtcMedications(int $page = 1, int $limit = 10): JsonResponse
    {
        $queryBuilder = $this->medicationRepository->createQueryBuilder('m')
            ->where('m.requiresPrescription = :prescription')
            ->andWhere('m.isActive = :active')
            ->setParameter('prescription', false)
            ->setParameter('active', true);
        return $this->paginationService->paginate($queryBuilder, $page, $limit, ["medication"]);
    }

    // IMPORT MEDICATIONS DATA FROM EXCEL/PDF FILES

    private function normalizeDci(string $dci): string
        {
        if (!$dci) {
            return '';
        }

        $dci = mb_convert_encoding($dci, 'UTF-8', 'ISO-8859-1');

        $dci = mb_strtolower($dci);

        $dci = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $dci);

        $dci = preg_replace('/[^a-z0-9]/', '', $dci);

        return trim($dci);
    }

    public static function normalizeDosage(string $dosage): string
    {
        $dosage = mb_strtolower($dosage);
        $dosage = str_replace(',', '.', $dosage);
        $dosage = preg_replace('/\s+/', '', $dosage);

        return trim($dosage);
    }

    public static function normalizeForm(string $form): string
    {
        $form = mb_strtolower($form);
        $form = \Normalizer::normalize($form, \Normalizer::FORM_D);
        $form = preg_replace('/\p{Mn}/u', '', $form);
        $form = preg_replace('/[^a-z]/u', '', $form);

        return trim($form);
    }
    public function createMedicationsFromExcel($file): JsonResponse
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        if(!$file){
            return $this->responsesService->errorResponse("Le fichier est requis");
        }

        if (!in_array($file->getClientOriginalExtension(), ['xlsx', 'xls'])) {
            return $this->responsesService->errorResponse("Le fichier doit être au format Excel");
        }

        // les champs du fichier sont : Nom, DCI, Prix Vente
        $reader = IOFactory::createReaderForFile($file->getPathName());
        $spreadsheet = $reader->load($file->getPathName());
        $worksheet = $spreadsheet->getActiveSheet();
        $data = [];
        for ($row = 2; $row <= $worksheet->getHighestRow(); $row++) {
            $name = $worksheet->getCell('A' . $row)->getValue();
            $dci = $worksheet->getCell('B' . $row)->getValue();
            $price = $worksheet->getCell('C' . $row)->getValue();
            $data[] = [
                "name" => $name,
                "dci" => $dci,
                "price" => $price,
                'normalizedDCI' => $this->normalizeDci($dci),
            ];
        }
        foreach ($data as $item){
            $medication = new Medication();
            $medication->setName($item['name']);
            $medication->setDci($item['dci']);
            $medication->setPrice($item['price']);
            $medication->setNormalizedDCI($item['normalizedDCI']);
            $this->entityHelper->save($medication);
            
        }
        return $this->responsesService->successResponse(["count" => count($data)], "Médicaments ajoutés");


    }
    private function extractCompositionsFromCleanText(string $text): array
    {
        $reimbursed = [];

        preg_match_all('/([A-Za-z\/\+\s]+)\s+[A-Za-z\s\-\(\)]+?\s+\d+[,\.]?\d*\s+\d+/', $text, $matches);

        foreach ($matches[1] as $rawComposition) {

            $normalized = $this->normalizeDci($rawComposition);

            if (!empty($normalized)) {
                $reimbursed[] = $normalized;
            }
        }

        return array_unique($reimbursed);
    }

    public function fetchRcpContent(string $cis): ?array
    {
        usleep(200000);

        $url = "https://base-donnees-publique.medicaments.gouv.fr/affichageDoc.php?specid=$cis&typedoc=R";

        $response = $this->httpClient->request('GET', $url, [
            'verify_peer' => false
        ]);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $html = $response->getContent();

        $crawler = new Crawler($html);

        $sections = [
            'description' => null,
            'posologie' => null,
            'sideEffects' => null,
            'contraindications' => null
        ];

        $crawler->filter('div.titre')->each(function (Crawler $node) use (&$sections) {

            $title = mb_strtolower(trim($node->text()));

            $contentNode = $node->nextAll()->first();

            if (!$contentNode->count()) {
                return;
            }

            $content = trim($contentNode->text());

            if (!$content) {
                return;
            }

            if (str_contains($title, 'indications')) {
                $sections['description'] = $content;
            }

            if (str_contains($title, 'posologie')) {
                $sections['posologie'] = $content;
            }

            if (str_contains($title, 'effets indésirables')) {
                $sections['sideEffects'] = $content;
            }

            if (str_contains($title, 'contre-indications')) {
                $sections['contraindications'] = $content;
            }
        });

        return $sections;
    }

    
    public function updateReimbursementStatus(array $reimbursedDcis): void
    {
        $em = $this->em;

        // reset
        $em->createQuery(
            "UPDATE App\Entity\Medication m
            SET m.isReimbursed = false,
                m.insuranceCoverage = 0"
        )->execute();

        if (empty($reimbursedDcis)) {
            return;
        }

        $em->createQuery(
            "UPDATE App\Entity\Medication m
            SET m.isReimbursed = true,
                m.insuranceCoverage = 70
            WHERE m.normalizedDCI IN (:dcis)"
        )
        ->setParameter('dcis', $reimbursedDcis)
        ->execute();
    }
    private function cleanCsvComposition(string $text): string
    {
        if (!$text) {
            return '';
        }

        // supprimer retours ligne
        $text = str_replace(["\r", "\n"], ' ', $text);

        // corriger mots coupés
        $text = preg_replace('/-\s+/', '', $text);

        // enlever espaces multiples
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
    public function extractReimbursedDcisFromCSV(string $filePath): array
    {
        $reimbursed = [];

        if (!file_exists($filePath)) {
            throw new \Exception("Fichier CSV introuvable");
        }

        $handle = fopen($filePath, 'r');

        // ignorer header
        $header = fgetcsv($handle, 0, ",");

        while (($row = fgetcsv($handle, 0, ",")) !== false) {

            // colonne COMPOSITION = index 2
            $composition = $row[2] ?? null;

            if (!$composition) {
                continue;
            }

            $composition = $this->cleanCsvComposition($composition);

            $normalized = $this->normalizeDci($composition);

            if ($normalized) {
                $reimbursed[] = $normalized;
            }
        }

        fclose($handle);

        return array_unique($reimbursed);
    }
    public function extractAndUpdateMedicationsCSV(File $file = NULL): JsonResponse
    {
        try {
            // $filePath = $this->uploadFileService->uploadFile($file);
            $filePath = $this->uploadFileService->getDocumentsDirectory('default') . '/' . '69aabb4ea39900.82488439.csv';

            $reimbursedDcis = $this->extractReimbursedDcisFromCSV($filePath);
            $this->updateReimbursementStatus($reimbursedDcis);
            return $this->responsesService->successResponse(["count" => count($reimbursedDcis)], "Médicaments mis à jour");
        } catch (\Exception $e) {
            return $this->responsesService->errorResponse($e->getMessage());
        }
    }
    // CHARGE DATA COMPLETIONS FOR MEDICATIONS FROM FRENCH GOVERNMENT DATABASE
    
    public function enrichMedicationsFromBdpm(){
        return $this->bdpm->enrich($this->uploadFileService->getDocumentsDirectory('default'));
    }

}