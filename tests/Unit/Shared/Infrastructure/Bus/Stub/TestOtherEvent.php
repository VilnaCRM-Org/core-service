<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class TestOtherEvent extends DomainEvent
{
    /**
     * @param array<string, string|object> $body
     */
    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): self {
        return new self($eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'test.other_event';
    }

    /**
     * @return array<string, string>
     */
    public function toPrimitives(): array
    {
        return [];
    }
}
