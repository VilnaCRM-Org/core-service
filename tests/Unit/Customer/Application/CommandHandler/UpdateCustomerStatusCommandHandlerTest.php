<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Customer\Application\CommandHandler\UpdateCustomerStatusCommandHandler;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateCustomerStatusCommandHandlerTest extends UnitTestCase
{
    private StatusRepositoryInterface|MockObject $repository;
    private UpdateCustomerStatusCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(StatusRepositoryInterface::class);

        $this->handler = new UpdateCustomerStatusCommandHandler(
            $this->repository
        );
    }

    public function testInvoke(): void
    {
        $value = $this->faker->word();
        $customerStatus = $this->createMock(CustomerStatus::class);
        $update = new CustomerStatusUpdate($value);
        $command = new UpdateCustomerStatusCommand($customerStatus, $update);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($customerStatus);

        $customerStatus
            ->expects($this->once())
            ->method('setValue')
            ->with($value);

        ($this->handler)($command);
    }

    public function testImplementsCommandHandlerInterface(): void
    {
        $this->assertInstanceOf(CommandHandlerInterface::class, $this->handler);
    }
}
