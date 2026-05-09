<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateTypeCommand;
use App\Core\Customer\Application\CommandHandler\CreateTypeCommandHandler;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Event\CustomerTypeCreatedEvent;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class CreateTypeCommandHandlerTest extends UnitTestCase
{
    private TypeRepositoryInterface|MockObject $repository;
    private EventBusInterface|MockObject $eventBus;
    private CreateTypeCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(TypeRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->handler = new CreateTypeCommandHandler(
            $this->repository,
            $this->eventBus
        );
    }

    public function testInvokeSavesTypeAndPublishesEvent(): void
    {
        $type = $this->createMock(CustomerType::class);
        $typeId = (string) $this->faker->ulid();
        $typeValue = $this->faker->word();
        $command = new CreateTypeCommand($type);

        $type->expects($this->once())
            ->method('getUlid')
            ->willReturn($typeId);

        $type->expects($this->once())
            ->method('getValue')
            ->willReturn($typeValue);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($type);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function ($event) use ($typeId, $typeValue): bool {
                return $event instanceof CustomerTypeCreatedEvent
                    && $event->customerTypeId() === $typeId
                    && $event->customerTypeValue() === $typeValue;
            }));

        ($this->handler)($command);
    }
}
