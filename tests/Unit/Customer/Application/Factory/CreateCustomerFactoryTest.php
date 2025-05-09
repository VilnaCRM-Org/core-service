<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Factory;

use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Application\Factory\CreateCustomerFactory;
use App\Core\Customer\Domain\Entity\Customer;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class CreateCustomerFactoryTest extends UnitTestCase
{
    public function testCreateCustomerCommand(): void
    {
        // Arrange: mock a Customer instance
        /** @var Customer&MockObject $customer */
        $customer = $this->createMock(Customer::class);

        // Act: invoke factory with the Customer
        $factory = new CreateCustomerFactory();
        $command = $factory->create($customer);

        // Assert: it returns a command wrapping the same Customer
        $this->assertInstanceOf(CreateCustomerCommand::class, $command);
        $this->assertSame($customer, $command->customer);
    }
}
