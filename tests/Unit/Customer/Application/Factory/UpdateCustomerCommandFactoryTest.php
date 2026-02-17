<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Factory;

use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactory;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Tests\Unit\UnitTestCase;

final class UpdateCustomerCommandFactoryTest extends UnitTestCase
{
    private UpdateCustomerCommandFactoryInterface $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new UpdateCustomerCommandFactory();
    }

    public function testCreate(): void
    {
        $customer = $this->createMock(Customer::class);

        $customerType = $this->createMock(CustomerType::class);
        $customerStatus = $this->createMock(CustomerStatus::class);

        $updateData = new CustomerUpdate(
            $this->faker->word(),
            $this->faker->email(),
            $this->faker->phoneNumber(),
            $this->faker->word(),
            $customerType,
            $customerStatus,
            true
        );

        $command = $this->factory->create($customer, $updateData);

        $this->assertCommand($command, $customer, $updateData);
    }

    private function assertCommand(
        UpdateCustomerCommand $command,
        Customer $expectedCustomer,
        CustomerUpdate $expectedUpdate
    ): void {
        $this->assertSame($expectedCustomer, $command->customer);
        $this->assertSame($expectedUpdate, $command->updateData);
    }
}
