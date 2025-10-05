<?php

namespace App\Command;

use App\Entity\Sale;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:generate-sales',
    description: 'Generate new Sale entries',
)]
class GenerateSalesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('🚀 Génération de ventes en continu... ');

        while (true) {
            sleep(random_int(15, 45));

            $faker = Factory::create('fr_FR');
            $sale = new Sale();
            $sale->setClient($faker->company());
            $sale->setAmount((string) $faker->randomFloat(2, 100, 10000));
            $sale->setStatus($faker->randomElement(['paid', 'pending', 'cancelled']));
            $sale->setDate(new \DateTime());

            $this->entityManager->persist($sale);
            $this->entityManager->flush();

            $output->writeln(sprintf(
                '[%s] ✅ Vente #%d créée : %s - %s €',
                date('H:i:s'),
                $sale->getId(),
                $sale->getClient(),
                number_format($sale->getAmount(), 2)
            ));
        }

        return Command::SUCCESS;
    }
}
