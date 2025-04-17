<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Uid\Ulid;

final class AppFixtures extends Fixture
{
    private Generator $faker;

    public function __construct(private UlidTransformer $ulidTransformer)
    {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        $statuses = ['Active', 'Inactive', 'Pending'];
        foreach ($statuses as $status) {
            $customerStatus = new CustomerStatus(
                $status,
                $this->ulidTransformer->transformFromSymfonyUlid(new Ulid())
            );
            $manager->persist($customerStatus);
        }

        for ($i = 1; $i <= 50; $i++) {
            $value = $this->faker->randomElement(['Active', 'Inactive', 'Pending', 'Archived', 'Under Review']);
            $customerStatus = new CustomerStatus(
                $value . ' ' . $i,
                $this->ulidTransformer->transformFromSymfonyUlid(new Ulid())
            );
            $manager->persist($customerStatus);
        }
        $manager->flush();
    }
}
