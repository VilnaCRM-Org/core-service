<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Factory;

use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Application\Factory\CreateCustomerFactory;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Tests\Unit\UnitTestCase;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;

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

        $typeValue = $this->faker->word();
        $typeUlidString = $this->faker->ulid();
        $customerType = new CustomerType(
            $typeValue,
            $ulidTransformer->transformFromSymfonyUlid($typeUlidString)
        );

        $statusValue = $this->faker->word();
        $statusUlidString = $this->faker->ulid();
        $customerStatus = new CustomerStatus(
            $statusValue,
            $ulidTransformer->transformFromSymfonyUlid($statusUlidString)
        );

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
}
