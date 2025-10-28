<?php

declare(strict_types=1);

namespace App\Tests\Behat\CustomerContext\Factory;

use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Factory\StatusFactoryInterface;
use App\Core\Customer\Domain\Factory\TypeFactoryInterface;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use Faker\Generator;

final readonly class TestEntityFactory
{
    public function __construct(
        private TypeFactoryInterface $typeFactory,
        private StatusFactoryInterface $statusFactory,
        private UlidFactory $ulidFactory,
        private Generator $faker
    ) {
    }

    public function createType(string $id, ?string $value = null): CustomerType
    {
        return $this->typeFactory->create(
            $value ?? $this->faker->word(),
            $this->ulidFactory->create($id)
        );
    }

    public function createStatus(string $id, ?string $value = null): CustomerStatus
    {
        return $this->statusFactory->create(
            $value ?? $this->faker->word(),
            $this->ulidFactory->create($id)
        );
    }

    /**
     * @return array{0: CustomerType, 1: CustomerStatus}
     */
    public function createTypeAndStatus(string $id): array
    {
        return [
            $this->createType($id),
            $this->createStatus($id),
        ];
    }

    /**
     * @return array{0: CustomerType, 1: CustomerStatus}
     */
    public function createTypeAndStatusWithValues(
        string $id,
        string $typeValue,
        string $statusValue
    ): array {
        return [
            $this->createType($id, $typeValue),
            $this->createStatus($id, $statusValue),
        ];
    }

    public function createUlid(string $id): Ulid
    {
        return $this->ulidFactory->create($id);
    }
}
