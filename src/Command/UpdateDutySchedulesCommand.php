<?php

namespace App\Command;

use App\Repository\PharmacyDutyScheduleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:update-duty-schedules',
    description: 'Met à jour le statut des pharmacies de garde expirées'
)]
class UpdateDutySchedulesCommand extends Command
{
    public function __construct(
        private readonly PharmacyDutyScheduleRepository $dutyScheduleRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = new \DateTime();
        
        $expiredSchedules = $this->dutyScheduleRepository->createQueryBuilder('ds')
            ->where('ds.endDate < :today')
            ->andWhere('ds.isActive = :active')
            ->setParameter('today', $today)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($expiredSchedules as $schedule) {
            $schedule->setIsActive(false);
            $this->entityManager->persist($schedule);
            $count++;
        }

        $this->entityManager->flush();

        $output->writeln(sprintf('Mise à jour terminée: %d plannings de garde expirés désactivés pour %s', 
            $count, 
            $today->format('Y-m-d')
        ));

        return Command::SUCCESS;
    }
}