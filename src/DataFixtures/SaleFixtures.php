<?php

namespace App\DataFixtures;

use App\Entity\Sale;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

final class SaleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 50; $i++) {
            $sale = new Sale();
            $sale->setDate($faker->dateTimeBetween('-3 months', 'now'));
            $sale->setClient($faker->company());
            $sale->setAmount((string) $faker->randomFloat(2, 100, 10000));
            $sale->setStatus($faker->randomElement(['paid', 'pending', 'cancelled']));

            $manager->persist($sale);
        }

        $manager->flush();
    }
}
