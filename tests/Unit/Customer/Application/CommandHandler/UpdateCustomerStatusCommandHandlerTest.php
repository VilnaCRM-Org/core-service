<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Core\Customer\Application\CommandHandler\UpdateStatusCommandHandler;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateCustomerStatusCommandHandlerTest extends UnitTestCase
{
    private StatusRepositoryInterface|MockObject $repository;
    private UpdateStatusCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(StatusRepositoryInterface::class);

        $this->handler = new UpdateStatusCommandHandler(
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
            ->method('update')
            ->with($update);

        ($this->handler)($command);
    }

    public function testImplementsCommandHandlerInterface(): void
    {
        $this->assertInstanceOf(CommandHandlerInterface::class, $this->handler);
    }
}
