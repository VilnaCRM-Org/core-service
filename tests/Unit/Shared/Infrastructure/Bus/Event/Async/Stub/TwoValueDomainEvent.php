<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class TwoValueDomainEvent extends DomainEvent
{
    public function __construct(
        private readonly string $first,
        private readonly string $second,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    public function eventName(): string
    {
        return 'test.two_value_domain_event';
    }

    /**
     * @return array<string, string>
     */
    public function toPrimitives(): array
    {
        return [
            'first' => $this->first,
            'second' => $this->second,
        ];
    }
}
