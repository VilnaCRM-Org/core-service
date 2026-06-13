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

    public function eventName(): string
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
        return 'customer_type_created_' . bin2hex(random_bytes(16));
    }
}
