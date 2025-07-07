<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Event;

/** @psalm-suppress UnusedClass */
interface EventBus
{
    /**
     * @param array<DomainEvent> $events
     */
    public function publish(array $events): void;
}
