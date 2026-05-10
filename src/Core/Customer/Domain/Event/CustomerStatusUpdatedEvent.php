<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

/**
 * Published when customer status reference data is updated.
 */
final class CustomerStatusUpdatedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $customerStatusId,
        private readonly string $currentValue,
        private readonly ?string $previousValue = null,
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
        return 'customer_status.updated';
    }

    /**
     * @return array<string, string|null>
     */
    public function toPrimitives(): array
    {
        return [
            'customer_status_id' => $this->customerStatusId,
            'current_value' => $this->currentValue,
            'previous_value' => $this->previousValue,
        ];
    }

    public function customerStatusId(): string
    {
        return $this->customerStatusId;
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

    private function generateEventId(): string
    {
        return uniqid('customer_status_updated_', true);
    }
}
