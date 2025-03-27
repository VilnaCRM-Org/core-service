<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Customer\Application\Command\UpdateCustomerTypeCommand;
use App\Customer\Application\CommandHandler\UpdateCustomerTypeCommandHandler;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Customer\Domain\ValueObject\CustomerTypeUpdate;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateCustomerTypeCommandHandlerTest extends UnitTestCase
{
    private TypeRepositoryInterface|MockObject $repository;
    private UpdateCustomerTypeCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(TypeRepositoryInterface::class);
        $this->handler = new UpdateCustomerTypeCommandHandler(
            $this->repository
        );
    }

    public function testInvoke(): void
    {
        $value = $this->faker->word();
        $customerType = $this->createMock(CustomerType::class);
        $update = new CustomerTypeUpdate($value);
        $command = new UpdateCustomerTypeCommand($customerType, $update);

        $customerType
            ->expects($this->once())
            ->method('setValue')
            ->with($value);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($customerType);

        ($this->handler)($command);
    }

    public function testImplementsCommandHandlerInterface(): void
    {
        $this->assertInstanceOf(
            CommandHandlerInterface::class,
            $this->handler
        );
    }
}
