<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event;

final class PartlyCoveredEventBus
{
    private array $events = [];

    public function addEvent(string $event): void
    {
        $this->events[] = $event;
    }

    public function getEventCount(): int
    {
        return count($this->events);
    }

    public function hasEvents(): bool
    {
        return !empty($this->events);
    }
}
