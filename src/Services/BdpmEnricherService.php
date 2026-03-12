<?php

namespace App\Services;

use App\Repository\MedicationRepository;
use App\Services\EntityHelperService;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Panther\Client;
use Symfony\Contracts\Cache\ItemInterface;

class BdpmEnricherService
{
    public function __construct(
        private MedicationRepository $medicationRepository,
        private EntityHelperService $entityHelper,
        private HttpClientInterface $http,
        private CacheInterface $cache
    ) {}
    

   

    
    
    
 
   


    public function enrich(string $bdpmDir): void
    {
        // dd($this->scrape('60514878'));
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $specialties = $this->loadBdpmSpecialties($bdpmDir.'/CIS_bdpm.txt');
        $compositions = $this->loadBdpmCompositions($bdpmDir.'/CIS_COMPO_bdpm.txt');
        $conditions = $this->loadBdpmConditions($bdpmDir.'/CIS_CPD_bdpm.txt');

        $qb = $this->medicationRepository->createQueryBuilder('m');
        $query = $qb->getQuery();

        $batchSize = 50;
        $i = 0;

        // cache pour éviter de scraper plusieurs fois le même CIS
        $scrapeCache = [];

    ;

        foreach ($query->toIterable() as $med) {

            $normalized = $this->normalizeSubstance($med->getNormalizedDCI());

            if (!$normalized || !isset($compositions[$normalized])) {
                continue;
            }

            $info = $compositions[$normalized][0];
            $cis = $info['cis'];

            if (!$cis) {
                continue;
            }

            if (!$med->getCis()) {
                $med->setCis($cis);
            }

            if (!empty($info['dosage'])) {
                $med->setDosage($this->normalizeDosage($info['dosage']));
            }

            if (!$med->getActiveIngredient() && $med->getNormalizedDCI()) {
                $med->setActiveIngredient($med->getNormalizedDCI());
            }

            if (isset($specialties[$cis])) {

                $spec = $specialties[$cis];

                if (!empty($spec['form'])) {
                    $med->setForm($spec['form']);
                }

                if (!empty($spec['manufacturer'])) {
                    $med->setManufacturer($spec['manufacturer']);
                }
            }

            if ($med->RequiresPrescription() === null && isset($conditions[$cis])) {
                $med->setRequiresPrescription($conditions[$cis]['prescription']);
            }

           $needsScraping =
            empty($med->getSideEffects()) ||
            empty($med->getContraindications()) ||
            empty($med->getDescription()) ||
            empty($med->getPosologie());

            if ($needsScraping) {
                $med->setSideEffects(['https://base-donnees-publique.medicaments.gouv.fr/medicament/'.$cis.'/extrait#4.3._Effets_indésirables']);
                $med->setContraindications(['https://base-donnees-publique.medicaments.gouv.fr/medicament/'.$cis.'/extrait#4.3._Contre-indications']);
                $med->setDescription('https://base-donnees-publique.medicaments.gouv.fr/medicament/'.$cis.'/extrait#4.1._Indications_therapeutiques');
                $med->setPosologie('https://base-donnees-publique.medicaments.gouv.fr/medicament/'.$cis.'/extrait#4.2._Posologie_et_mode_d_administration');
                
            }
               

                
            

            $this->entityHelper->persistWithoutFlush($med);

            if ((++$i % $batchSize) === 0) {

                $this->entityHelper->flush();
                $this->entityHelper->clear();

                gc_collect_cycles();
            }
        }

        $this->entityHelper->flush();

       
    }
    private function openBdpmFile(string $file): \Generator
    {
        $handle = fopen($file, 'r');

        while (($line = fgets($handle)) !== false) {

            $line = mb_convert_encoding($line, 'UTF-8', 'ISO-8859-1');

            $line = trim($line);

            if ($line === '') {
                continue;
            }

            yield explode("\t", $line);
        }

        fclose($handle);
    }

    
    private function loadBdpmSpecialties(string $file): array
    {
        $data = [];

        foreach ($this->openBdpmFile($file) as $row) {

            $cis = $row[0];

            $data[$cis] = [
                'name' => $this->cleanString($row[1] ?? null),
                'form' => $this->cleanString($row[2] ?? null),
                'manufacturer' => $this->cleanString($row[9] ?? null)
            ];
        }

        return $data;
    }

    private function loadBdpmCompositions(string $file): array
    {
        $map = [];

        foreach ($this->openBdpmFile($file) as $row) {

            $cis = $row[0];

            $substance = $this->normalizeSubstance($row[3] ?? null);

            $dosage = $row[4] ?? null;

            if (!$substance) {
                continue;
            }

            $map[$substance][] = [
                'cis' => $cis,
                'dosage' => $dosage
            ];
        }

        return $map;
    }

    private function loadBdpmConditions(string $file): array
    {
        $data = [];

        foreach ($this->openBdpmFile($file) as $row) {

            $cis = $row[0];

            $condition = strtolower($row[1] ?? '');

            $data[$cis] = [
                'prescription' => str_contains($condition, 'hospital')
                    || str_contains($condition, 'prescription')
            ];
        }

        return $data;
    }

    private function normalizeSubstance(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        $name = strtolower($name);

        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);

        $name = preg_replace('/[^a-z0-9]/', '', $name);

        return trim($name);
    }

    private function normalizeDosage(?string $dosage): ?string
    {
        if (!$dosage) {
            return null;
        }

        $dosage = str_replace(',', '.', $dosage);

        if (preg_match('/(\d+(?:\.\d+)?)\s*(mg|g|ml|ui|µg)/i', $dosage, $m)) {

            $value = rtrim(rtrim($m[1], '0'), '.');

            return $value . strtolower($m[2]);
        }

        return trim($dosage);
    }

    private function cleanString(?string $value): ?string
    {
            if (!$value) {
            return null;
        }

        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        $value = preg_replace('/\s+/', ' ', $value);
        
        $value = preg_replace('/[[:cntrl:]]/', '', $value);
        return trim($value);
    }

   
}