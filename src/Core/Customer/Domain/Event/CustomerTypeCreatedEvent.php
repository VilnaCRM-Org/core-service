<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

/**
 * Published when customer type reference data is created.
 */
final class CustomerTypeCreatedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $customerTypeId,
        private readonly string $customerTypeValue,
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
            customerTypeId: $body['customer_type_id'],
            customerTypeValue: $body['customer_type_value'],
            eventId: $eventId,
            occurredOn: $occurredOn
        );
    }

    public static function eventName(): string
    {
        return 'customer_type.created';
    }

    /**
     * @return array<string, string>
     */
    public function toPrimitives(): array
    {
        return [
            'customer_type_id' => $this->customerTypeId,
            'customer_type_value' => $this->customerTypeValue,
        ];
    }

    public function customerTypeId(): string
    {
        return $this->customerTypeId;
    }

    public function customerTypeValue(): string
    {
        return $this->customerTypeValue;
    }

    private function generateEventId(): string
    {
        return uniqid('customer_type_created_', true);
    }
}
