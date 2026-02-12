<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Infrastructure\Bus\Event\PartlyCoveredEventBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBus;

final class PartlyCoveredEventBusTest extends TestCase
{
    public function testPublish(): void
    {
        $event = $this->createMock(DomainEvent::class);
        $bus = $this->createMock(MessageBus::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($event)
            ->willReturn(new Envelope($event));

        $eventBus = new PartlyCoveredEventBus($bus);
        $eventBus->publish($event);
    }

    // Note: getEventCount is intentionally NOT tested to keep this class partly covered
}
