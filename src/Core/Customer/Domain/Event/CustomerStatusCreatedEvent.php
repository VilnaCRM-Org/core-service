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

    public function eventName(): string
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
        return 'customer_status_created_' . bin2hex(random_bytes(16));
    }
}
