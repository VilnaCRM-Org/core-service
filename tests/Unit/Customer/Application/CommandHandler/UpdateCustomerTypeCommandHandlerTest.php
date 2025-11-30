<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\UpdateCustomerTypeCommand;
use App\Core\Customer\Application\CommandHandler\UpdateTypeCommandHandler;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerTypeUpdate;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateCustomerTypeCommandHandlerTest extends UnitTestCase
{
    private TypeRepositoryInterface|MockObject $repository;
    private UpdateTypeCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(TypeRepositoryInterface::class);
        $this->handler = new UpdateTypeCommandHandler(
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
            ->method('update')
            ->with($update);

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
