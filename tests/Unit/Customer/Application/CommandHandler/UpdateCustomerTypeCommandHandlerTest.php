<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\UpdateCustomerTypeCommand;
use App\Core\Customer\Application\CommandHandler\UpdateTypeCommandHandler;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Event\CustomerTypeUpdatedEvent;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerTypeUpdate;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateCustomerTypeCommandHandlerTest extends UnitTestCase
{
    private TypeRepositoryInterface|MockObject $repository;
    private EventBusInterface|MockObject $eventBus;
    private UpdateTypeCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(TypeRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->handler = new UpdateTypeCommandHandler(
            $this->repository,
            $this->eventBus
        );
    }

    public function testInvoke(): void
    {
        $previousValue = 'lead';
        $value = 'customer';
        $typeId = (string) $this->faker->ulid();
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

        $customerType
            ->expects($this->once())
            ->method('getUlid')
            ->willReturn($typeId);

        $customerType
            ->expects($this->exactly(2))
            ->method('getValue')
            ->willReturn($previousValue, $value);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function ($event) use ($typeId, $value, $previousValue): bool {
                return $event instanceof CustomerTypeUpdatedEvent
                    && $event->customerTypeId() === $typeId
                    && $event->currentValue() === $value
                    && $event->previousValue() === $previousValue
                    && $event->valueChanged();
            }));

        ($this->handler)($command);
    }

    public function testInvokePublishesEventWithoutPreviousValueWhenValueIsUnchanged(): void
    {
        $value = 'customer';
        $typeId = (string) $this->faker->ulid();
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

        $customerType
            ->expects($this->once())
            ->method('getUlid')
            ->willReturn($typeId);

        $customerType
            ->expects($this->exactly(2))
            ->method('getValue')
            ->willReturn($value);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function ($event) use ($typeId, $value): bool {
                return $event instanceof CustomerTypeUpdatedEvent
                    && $event->customerTypeId() === $typeId
                    && $event->currentValue() === $value
                    && $event->previousValue() === null
                    && ! $event->valueChanged();
            }));

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
