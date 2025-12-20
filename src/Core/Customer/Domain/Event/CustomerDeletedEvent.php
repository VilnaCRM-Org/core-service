<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

/**
 * Customer Deleted Domain Event
 *
 * Published when a customer is deleted.
 * Triggers cache invalidation for the deleted customer.
 */
final class CustomerDeletedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $customerId,
        private readonly string $customerEmail,
        ?string $eventId = null,
        ?string $occurredOn = null
    ) {
        parent::__construct(
            $eventId ?? $this->generateEventId(),
            $occurredOn
        );
    }

    /**
     * @param array<string, string> $body
     */
    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): self {
        return new self(
            customerId: $body['customer_id'],
            customerEmail: $body['customer_email'],
            eventId: $eventId,
            occurredOn: $occurredOn
        );
    }

    public static function eventName(): string
    {
        return 'customer.deleted';
    }

    /**
     * @return array<string, string>
     */
    public function toPrimitives(): array
    {
        return [
            'customer_id' => $this->customerId,
            'customer_email' => $this->customerEmail,
        ];
    }

    public function customerId(): string
    {
        return $this->customerId;
    }

    public function customerEmail(): string
    {
        return $this->customerEmail;
    }

    private function generateEventId(): string
    {
        return uniqid('customer_deleted_', true);
    }
}
