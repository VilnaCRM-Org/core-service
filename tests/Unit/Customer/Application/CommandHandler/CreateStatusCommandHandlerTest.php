<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Core\Customer\Application\CommandHandler\CreateStatusCommandHandler;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Event\CustomerStatusCreatedEvent;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class CreateStatusCommandHandlerTest extends UnitTestCase
{
    private StatusRepositoryInterface|MockObject $repository;
    private EventBusInterface|MockObject $eventBus;
    private CreateStatusCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(StatusRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->handler = new CreateStatusCommandHandler(
            $this->repository,
            $this->eventBus
        );
    }

    public function testInvokeSavesStatusAndPublishesEvent(): void
    {
        $status = $this->createMock(CustomerStatus::class);
        $statusId = (string) $this->faker->ulid();
        $statusValue = $this->faker->word();
        $command = new CreateStatusCommand($status);

        $status->expects($this->once())
            ->method('getUlid')
            ->willReturn($statusId);

        $status->expects($this->once())
            ->method('getValue')
            ->willReturn($statusValue);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($status);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function ($event) use ($statusId, $statusValue): bool {
                return $event instanceof CustomerStatusCreatedEvent
                    && $event->customerStatusId() === $statusId
                    && $event->customerStatusValue() === $statusValue;
            }));

        ($this->handler)($command);
    }
}
