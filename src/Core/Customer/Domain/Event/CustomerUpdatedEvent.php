<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

/**
 * Customer Updated Domain Event
 *
 * Published when a customer is updated.
 * Triggers cache invalidation for the customer.
 * Handles email change invalidation (both old and new email hashes).
 */
final class CustomerUpdatedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $customerId,
        private readonly string $currentEmail,
        private readonly ?string $previousEmail = null,
        ?string $eventId = null,
        ?string $occurredOn = null
    ) {
        parent::__construct(
            $eventId ?? $this->generateEventId(),
            $occurredOn
        );
    }

    /**
     * @param array<string, string|null> $body
     */
    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): self {
        return new self(
            customerId: $body['customer_id'],
            currentEmail: $body['current_email'],
            previousEmail: $body['previous_email'] ?? null,
            eventId: $eventId,
            occurredOn: $occurredOn
        );
    }

    public static function eventName(): string
    {
        return 'customer.updated';
    }

    /**
     * @return array<string, string|null>
     */
    public function toPrimitives(): array
    {
        return [
            'customer_id' => $this->customerId,
            'current_email' => $this->currentEmail,
            'previous_email' => $this->previousEmail,
        ];
    }

    public function customerId(): string
    {
        return $this->customerId;
    }

    public function currentEmail(): string
    {
        return $this->currentEmail;
    }

    public function previousEmail(): ?string
    {
        return $this->previousEmail;
    }

    /**
     * Check if email changed during update
     *
     * Used by cache invalidation subscriber to determine if both
     * old and new email caches need invalidation.
     */
    public function emailChanged(): bool
    {
        return $this->previousEmail !== null
            && $this->previousEmail !== $this->currentEmail;
    }

    private function generateEventId(): string
    {
        return uniqid('customer_updated_', true);
    }
}
