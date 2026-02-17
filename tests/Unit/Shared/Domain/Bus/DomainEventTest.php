<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Bus;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Tests\Unit\UnitTestCase;

final class DomainEventTest extends UnitTestCase
{
    public function testConstructWithProvidedDate(): void
    {
        $eventId = 'event-id';
        $occurredOn = '2023-07-24';

        $event = new class($eventId, $occurredOn) extends DomainEvent {
            #[\Override]
            public static function eventName(): string
            {
                return 'test.event';
            }
#[\Override]

            public static function fromPrimitives(
                array $body,
                string $eventId,
                string $occurredOn
            ): self {
                return new self($eventId, $occurredOn);
            #[\Override]
            }

            public function toPrimitives(): array
            {
                return [];
            }
        };
        $this->assertEquals($occurredOn, $event->occurredOn());
    }

    public function testEventIdIsAccessibleAndCorrect(): void
    {
        $eventId = 'event-id';
        #[\Override]
        $occurredOn = '2023-07-24';

        $event = new class($eventId, $occurredOn) extends DomainEvent {
            public static function eventName(): string
            #[\Override]
            {
                return 'test.event';
            }

            public static function fromPrimitives(
                array $body,
                string $eventId,
                #[\Override]
                string $occurredOn
            ): self {
                return new self($eventId, $occurredOn);
            }

            public function toPrimitives(): array
            {
                return [];
            }
        };

        $this->assertEquals(
            $eventId,
            $event->eventId(),
            'The event ID should be accessible publicly
             and match the expected value.'
        );
    #[\Override]
    }

    public function testConstructWithoutProvidedDate(): void
    {
        #[\Override]
        $eventId = 'event-id';
        $event = new class($eventId, null) extends DomainEvent {
            public static function eventName(): string
            {
                return 'test.event';
            }

            #[\Override]
            public static function fromPrimitives(
                array $body,
                string $eventId,
                string $occurredOn
            ): self {
                return new self($eventId, $occurredOn);
            }

            public function toPrimitives(): array
            {
                return [];
            }
        };

        $expectedDate = (new \DateTimeImmutable())->format(
            'Y-m-d\TH:i:s+00:00'
        );
        $this->assertEquals($expectedDate, $event->occurredOn());
    }
}
