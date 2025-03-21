<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Factory;

use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Application\Factory\CreateCustomerFactory;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;

final class CreateCustomerFactoryTest extends UnitTestCase
{
    public function testCreateCustomerCommand(): void
    {
        $initials = $this->faker->name();
        $email = $this->faker->email();
        $phone = $this->faker->phoneNumber();
        $leadSource = $this->faker->word();
        $confirmed = $this->faker->boolean();

        $ulidTransformer = new UlidTransformer(new UlidFactory());
        $customerType = $this->createCustomerType($ulidTransformer);
        $customerStatus = $this->createCustomerStatus($ulidTransformer);

        $factory = new CreateCustomerFactory();
        $command = $factory->create(
            $initials,
            $email,
            $phone,
            $leadSource,
            $customerType,
            $customerStatus,
            $confirmed
        );

        $this->assertInstanceOf(CreateCustomerCommand::class, $command);
    }

    private function createCustomerType(
        UlidTransformer $ulidTransformer
    ): CustomerType {
        $typeValue = $this->faker->word();
        $typeUlidString = $this->faker->ulid();

        return new CustomerType(
            $typeValue,
            $ulidTransformer->transformFromSymfonyUlid($typeUlidString)
        );
    }

    private function createCustomerStatus(
        UlidTransformer $ulidTransformer
    ): CustomerStatus {
        $statusValue = $this->faker->word();
        $statusUlidString = $this->faker->ulid();

        return new CustomerStatus(
            $statusValue,
            $ulidTransformer->transformFromSymfonyUlid($statusUlidString)
        );
    }
}
