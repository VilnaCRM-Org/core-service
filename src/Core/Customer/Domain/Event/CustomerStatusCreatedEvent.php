<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

/**
 * Published when customer status reference data is created.
 */
final class CustomerStatusCreatedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $customerStatusId,
        private readonly string $customerStatusValue,
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
            customerStatusId: $body['customer_status_id'],
            customerStatusValue: $body['customer_status_value'],
            eventId: $eventId,
            occurredOn: $occurredOn
        );
    }

    public static function eventName(): string
    {
        return 'customer_status.created';
    }

    /**
     * @return array<string, string>
     */
    public function toPrimitives(): array
    {
        return [
            'customer_status_id' => $this->customerStatusId,
            'customer_status_value' => $this->customerStatusValue,
        ];
    }

    public function customerStatusId(): string
    {
        return $this->customerStatusId;
    }

    public function customerStatusValue(): string
    {
        return $this->customerStatusValue;
    }

    private function generateEventId(): string
    {
        return uniqid('customer_status_created_', true);
    }
}
