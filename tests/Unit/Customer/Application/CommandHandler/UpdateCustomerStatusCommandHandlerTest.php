<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Core\Customer\Application\CommandHandler\UpdateStatusCommandHandler;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Event\CustomerStatusUpdatedEvent;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateCustomerStatusCommandHandlerTest extends UnitTestCase
{
    private StatusRepositoryInterface|MockObject $repository;
    private EventBusInterface|MockObject $eventBus;
    private UpdateStatusCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(StatusRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);

        $this->handler = new UpdateStatusCommandHandler(
            $this->repository,
            $this->eventBus
        );
    }

    public function testInvoke(): void
    {
        $previousValue = 'inactive';
        $value = 'active';
        $statusId = (string) $this->faker->ulid();
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

        $customerStatus
            ->expects($this->once())
            ->method('getUlid')
            ->willReturn($statusId);

        $customerStatus
            ->expects($this->exactly(2))
            ->method('getValue')
            ->willReturn($previousValue, $value);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function ($event) use ($statusId, $value, $previousValue): bool {
                return $event instanceof CustomerStatusUpdatedEvent
                    && $event->customerStatusId() === $statusId
                    && $event->currentValue() === $value
                    && $event->previousValue() === $previousValue
                    && $event->valueChanged();
            }));

        ($this->handler)($command);
    }

    public function testInvokePublishesEventWithoutPreviousValueWhenValueIsUnchanged(): void
    {
        $value = 'active';
        $statusId = (string) $this->faker->ulid();
        $customerStatus = $this->createMock(CustomerStatus::class);
        $update = new CustomerStatusUpdate($value);
        $command = new UpdateCustomerStatusCommand($customerStatus, $update);

        $customerStatus
            ->expects($this->once())
            ->method('update')
            ->with($update);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($customerStatus);

        $customerStatus
            ->expects($this->once())
            ->method('getUlid')
            ->willReturn($statusId);

        $customerStatus
            ->expects($this->exactly(2))
            ->method('getValue')
            ->willReturn($value);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function ($event) use ($statusId, $value): bool {
                return $event instanceof CustomerStatusUpdatedEvent
                    && $event->customerStatusId() === $statusId
                    && $event->currentValue() === $value
                    && $event->previousValue() === null
                    && ! $event->valueChanged();
            }));

        ($this->handler)($command);
    }

    public function testImplementsCommandHandlerInterface(): void
    {
        $this->assertInstanceOf(CommandHandlerInterface::class, $this->handler);
    }
}
