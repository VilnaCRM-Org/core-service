<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Event;

use DateTimeImmutable;
use DateTimeInterface;

abstract class DomainEvent
{
    private readonly string $eventId;
    private readonly string $occurredOn;

    public function __construct(string $eventId, ?string $occurredOn)
    {
        $this->eventId = $eventId;
        $this->occurredOn = $occurredOn ??
            self::dateToString(new DateTimeImmutable());
    }

    abstract public function eventName(): string;

    /**
     * @return array<string, string|object>
     */
    abstract public function toPrimitives(): array;

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function occurredOn(): string
    {
        return $this->occurredOn;
    }

    protected function generateEventId(string $prefix): string
    {
        return $prefix . bin2hex(random_bytes(16));
    }

    private function dateToString(DateTimeInterface $date): string
    {
        return $date->format(DateTimeInterface::ATOM);
    }
}
