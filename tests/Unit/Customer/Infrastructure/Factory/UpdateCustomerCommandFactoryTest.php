<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Factory;

use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactory;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Tests\Unit\UnitTestCase;

final class UpdateCustomerCommandFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $customer = $this->createMock(Customer::class);
        $type = $this->createMock(CustomerType::class);
        $status = $this->createMock(CustomerStatus::class);

        $updateData = new CustomerUpdate(
            newInitials: $this->faker->word(),
            newEmail: $this->faker->email(),
            newPhone: $this->faker->phoneNumber(),
            newLeadSource: $this->faker->word(),
            newType: $type,
            newStatus: $status,
            newConfirmed: true
        );

        $factory = new UpdateCustomerCommandFactory();
        $command = $factory->create($customer, $updateData);

        $this->assertInstanceOf(UpdateCustomerCommand::class, $command);
    }
}
