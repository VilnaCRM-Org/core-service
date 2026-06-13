<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

/**
 * Published when customer type reference data is updated.
 */
final class CustomerTypeUpdatedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $customerTypeId,
        private readonly string $currentValue,
        private readonly ?string $previousValue = null,
        ?string $eventId = null,
        ?string $occurredOn = null
    ) {
        parent::__construct(
            $eventId ?? $this->generateEventId('customer_type_updated_'),
            $occurredOn
        );
    }

    public function eventName(): string
    {
        return 'customer_type.updated';
    }

    /**
     * @return array<string, string|null>
     */
    public function toPrimitives(): array
    {
        return [
            'customer_type_id' => $this->customerTypeId,
            'current_value' => $this->currentValue,
            'previous_value' => $this->previousValue,
        ];
    }

    public function customerTypeId(): string
    {
        return $this->customerTypeId;
    }

    public function currentValue(): string
    {
        return $this->currentValue;
    }

    public function previousValue(): ?string
    {
        return $this->previousValue;
    }

    public function valueChanged(): bool
    {
        return $this->previousValue !== null
            && $this->previousValue !== $this->currentValue;
    }
}
