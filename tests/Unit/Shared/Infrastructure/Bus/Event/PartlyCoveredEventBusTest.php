<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Infrastructure\Bus\Event\PartlyCoveredEventBus;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\MessageBus;

final class PartlyCoveredEventBusTest extends UnitTestCase
{
    public function testPublishDispatchesEventsToMessageBus(): void
    {
        $messageBus = $this->createMock(MessageBus::class);
        $event1 = $this->createMock(DomainEvent::class);
        $event2 = $this->createMock(DomainEvent::class);

        $messageBus->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive([$event1], [$event2]);

        $eventBus = new PartlyCoveredEventBus($messageBus);
        $eventBus->publish($event1, $event2);
    }

    public function testGetEventCountReturnsCorrectCount(): void
    {
        $messageBus = $this->createMock(MessageBus::class);
        $eventBus = new PartlyCoveredEventBus($messageBus);

        $event1 = $this->createMock(DomainEvent::class);
        $event2 = $this->createMock(DomainEvent::class);
        $nonEvent = new \stdClass();

        $this->assertSame(2, $eventBus->getEventCount([$event1, $event2]));
        $this->assertSame(2, $eventBus->getEventCount([$event1, $event2, $nonEvent]));
        $this->assertSame(0, $eventBus->getEventCount([$nonEvent]));
        $this->assertSame(0, $eventBus->getEventCount([]));
    }
}
