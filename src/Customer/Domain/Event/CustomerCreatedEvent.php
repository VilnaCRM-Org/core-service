<?php

namespace App\Customer\Domain\Event;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;

class CustomerCreatedEvent extends DomainEvent
{

    public function __construct(
        public readonly CustomerInterface $customer,
        string $eventId,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    /**
     * @param array<string, Customer> $body
     */
    public static function fromPrimitives(
        array $body,
        string $eventId,
        string $occurredOn
    ): DomainEvent {
        return new self($body['customer'], $eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'customer.created';
    }

    /**
     * @return array<string, Customer>
     */
    public function toPrimitives(): array
    {
        return [
            'customer' => $this->customer,
        ];
    }
}