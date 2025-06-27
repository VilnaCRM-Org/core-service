<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Event;

use App\Shared\Domain\ValueObject\Ulid;
use DateTimeImmutable;

abstract class DomainEvent
{
    public function __construct(
        private string $aggregateId,
        private string $eventId,
        private string $occurredOn
    ) {
    }

    /**
     * @param array<string, string> $body
     * @psalm-suppress PossiblyUnusedParam
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn
    ): static {
        return new static($aggregateId, $eventId, $occurredOn);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    /**
     * @return array<string, string>
     * @psalm-suppress PossiblyUnusedMethod
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

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function eventName(): string
    {
        return static::class;
    }

    /**
     * @param array<string, string|int|bool|float|null> $body
     * @psalm-suppress PossiblyUnusedParam
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function fromDomain(
        string $aggregateId,
        array $body = [],
        ?string $eventId = null,
        ?string $occurredOn = null
    ): static {
        return new static(
            $aggregateId,
            $eventId ?? Ulid::random()->value(),
            $occurredOn ?? (new DateTimeImmutable())->format(
            DateTimeImmutable::ATOM
        )
        );
    }
}
