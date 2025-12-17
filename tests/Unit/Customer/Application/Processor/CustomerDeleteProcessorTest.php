<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Core\Customer\Application\Command\DeleteCustomerCommand;
use App\Core\Customer\Application\Processor\CustomerDeleteProcessor;
use App\Core\Customer\Domain\Entity\Customer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;

final class CustomerDeleteProcessorTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private CustomerDeleteProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->processor = new CustomerDeleteProcessor($this->commandBus);
    }

    public function testProcessDispatchesDeleteCommand(): void
    {
        $customer = $this->createMock(Customer::class);
        $operation = $this->createMock(Operation::class);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(DeleteCustomerCommand::class));

        $result = $this->processor->process($customer, $operation);

        self::assertSame($customer, $result);
    }

    public function testProcessThrowsWhenDataIsNotCustomer(): void
    {
        $operation = $this->createMock(Operation::class);

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected instance of Customer');

        $this->processor->process(new \stdClass(), $operation);
    }
}
