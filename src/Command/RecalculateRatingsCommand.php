<?php

namespace App\Command;

use App\Services\RatingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:recalculate-ratings',
    description: 'Recalcule tous les ratings des médecins et établissements'
)]
class RecalculateRatingsCommand extends Command
{
    public function __construct(
        private readonly RatingService $ratingService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Recalcul des ratings en cours...');

        $this->ratingService->recalculateAllRatings();

        $io->success('Tous les ratings ont été recalculés avec succès !');

        return Command::SUCCESS;
    }
}