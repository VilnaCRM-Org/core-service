<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Application\CommandHandler\CreateCustomerCommandHandler;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class CreateCustomerCommandHandlerTest extends UnitTestCase
{
    private CustomerRepositoryInterface&MockObject $repository;
    private CreateCustomerCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(
            CustomerRepositoryInterface::class
        );
        $this->handler = new CreateCustomerCommandHandler($this->repository);
    }

    public function testInvokeSavesCustomer(): void
    {
        $customer = $this->createMock(Customer::class);

        $command = new CreateCustomerCommand($customer);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($customer);

        ($this->handler)($command);
    }
}
