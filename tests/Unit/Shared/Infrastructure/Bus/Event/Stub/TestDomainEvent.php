<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class TestDomainEvent extends DomainEvent
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        private readonly string $id,
        private readonly string $value,
        ?string $eventId = null,
        ?string $occurredOn = null
    ) {
        parent::__construct(
            $eventId ?? $this->generateEventId(),
            $occurredOn
        );
    }

    public function eventName(): string
    {
        return 'test.domain_event';
    }

    public function toPrimitives(): array
    {
        return [
            'id' => $this->id,
            'value' => $this->value,
        ];
    }

    private function generateEventId(): string
    {
        return uniqid('test_domain_event_', true);
    }
}
