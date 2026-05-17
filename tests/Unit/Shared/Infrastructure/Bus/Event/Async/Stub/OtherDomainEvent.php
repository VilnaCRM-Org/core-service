<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;

/**
 * Another test domain event that TestDomainEventSubscriber does NOT subscribe to
 */
final class OtherDomainEvent extends DomainEvent
{
    public function __construct(
        private readonly string $data,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    public function eventName(): string
    {
        return 'test.other_domain_event';
    }

    /**
     * @return array<string, string>
     */
    public function toPrimitives(): array
    {
        return ['data' => $this->data];
    }
}
