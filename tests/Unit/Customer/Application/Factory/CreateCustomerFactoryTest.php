<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Factory;

use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Application\Factory\CreateCustomerFactory;
use App\Core\Customer\Domain\Entity\CustomerInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class CreateCustomerFactoryTest extends UnitTestCase
{
    public function testCreateCustomerCommand(): void
    {
        /** @var CustomerInterface&MockObject $customer */
        $customer = $this->createMock(CustomerInterface::class);

        $factory = new CreateCustomerFactory();
        $command = $factory->create($customer);

        $this->assertInstanceOf(CreateCustomerCommand::class, $command);
        $this->assertSame($customer, $command->customer);
    }
}
