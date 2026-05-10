<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class TestDomainEvent extends DomainEvent
{
    public function __construct(
        private readonly string $aggregateId,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    public function eventName(): string
    {
        return 'test.domain_event';
    }

    /**
     * @return array<string, string>
     */
    public function toPrimitives(): array
    {
        return ['aggregateId' => $this->aggregateId];
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }
}
