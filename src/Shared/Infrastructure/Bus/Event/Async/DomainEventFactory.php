<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event\Async;

use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Core\Customer\Domain\Event\CustomerStatusCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerStatusUpdatedEvent;
use App\Core\Customer\Domain\Event\CustomerTypeCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerTypeUpdatedEvent;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Shared\Domain\Bus\Event\DomainEvent;
use InvalidArgumentException;
use ReflectionClass;

final readonly class DomainEventFactory
{
    public function fromEnvelope(DomainEventEnvelope $envelope): DomainEvent
    {
        $body = $envelope->body();
        $eventFactory = $this->knownEventFactories()[$envelope->eventClass()] ?? null;

        return $eventFactory === null
            ? $this->fromConstructor($envelope, $body)
            : $eventFactory($envelope, $body);
    }

    /**
     * @return array<
     *     class-string<DomainEvent>,
     *     callable(DomainEventEnvelope, array<string, string|object|null>): DomainEvent
     * >
     */
    private function knownEventFactories(): array
    {
        return [
            CustomerCreatedEvent::class => $this->customerCreatedEvent(...),
            CustomerUpdatedEvent::class => $this->customerUpdatedEvent(...),
            CustomerDeletedEvent::class => $this->customerDeletedEvent(...),
            CustomerStatusCreatedEvent::class => $this->customerStatusCreatedEvent(...),
            CustomerStatusUpdatedEvent::class => $this->customerStatusUpdatedEvent(...),
            CustomerTypeCreatedEvent::class => $this->customerTypeCreatedEvent(...),
            CustomerTypeUpdatedEvent::class => $this->customerTypeUpdatedEvent(...),
        ];
    }

    /**
     * @param array<string, string|object|null> $body
     */
    private function customerCreatedEvent(
        DomainEventEnvelope $envelope,
        array $body
    ): CustomerCreatedEvent {
        return new CustomerCreatedEvent(
            customerId: (string) $body['customer_id'],
            customerEmail: (string) $body['customer_email'],
            eventId: $envelope->eventId(),
            occurredOn: $envelope->occurredOn()
        );
    }

    /**
     * @param array<string, string|object|null> $body
     */
    private function customerUpdatedEvent(
        DomainEventEnvelope $envelope,
        array $body
    ): CustomerUpdatedEvent {
        return new CustomerUpdatedEvent(
            customerId: (string) $body['customer_id'],
            currentEmail: (string) $body['current_email'],
            previousEmail: $this->nullableBodyString($body, 'previous_email'),
            eventId: $envelope->eventId(),
            occurredOn: $envelope->occurredOn()
        );
    }

    /**
     * @param array<string, string|object|null> $body
     */
    private function customerDeletedEvent(
        DomainEventEnvelope $envelope,
        array $body
    ): CustomerDeletedEvent {
        return new CustomerDeletedEvent(
            customerId: (string) $body['customer_id'],
            customerEmail: (string) $body['customer_email'],
            eventId: $envelope->eventId(),
            occurredOn: $envelope->occurredOn()
        );
    }

    /**
     * @param array<string, string|object|null> $body
     */
    private function customerStatusCreatedEvent(
        DomainEventEnvelope $envelope,
        array $body
    ): CustomerStatusCreatedEvent {
        return new CustomerStatusCreatedEvent(
            customerStatusId: (string) $body['customer_status_id'],
            customerStatusValue: (string) $body['customer_status_value'],
            eventId: $envelope->eventId(),
            occurredOn: $envelope->occurredOn()
        );
    }

    /**
     * @param array<string, string|object|null> $body
     */
    private function customerStatusUpdatedEvent(
        DomainEventEnvelope $envelope,
        array $body
    ): CustomerStatusUpdatedEvent {
        return new CustomerStatusUpdatedEvent(
            customerStatusId: (string) $body['customer_status_id'],
            currentValue: (string) $body['current_value'],
            previousValue: $this->nullableBodyString($body, 'previous_value'),
            eventId: $envelope->eventId(),
            occurredOn: $envelope->occurredOn()
        );
    }

    /**
     * @param array<string, string|object|null> $body
     */
    private function customerTypeCreatedEvent(
        DomainEventEnvelope $envelope,
        array $body
    ): CustomerTypeCreatedEvent {
        return new CustomerTypeCreatedEvent(
            customerTypeId: (string) $body['customer_type_id'],
            customerTypeValue: (string) $body['customer_type_value'],
            eventId: $envelope->eventId(),
            occurredOn: $envelope->occurredOn()
        );
    }

    /**
     * @param array<string, string|object|null> $body
     */
    private function customerTypeUpdatedEvent(
        DomainEventEnvelope $envelope,
        array $body
    ): CustomerTypeUpdatedEvent {
        return new CustomerTypeUpdatedEvent(
            customerTypeId: (string) $body['customer_type_id'],
            currentValue: (string) $body['current_value'],
            previousValue: $this->nullableBodyString($body, 'previous_value'),
            eventId: $envelope->eventId(),
            occurredOn: $envelope->occurredOn()
        );
    }

    /**
     * @param array<string, string|object|null> $body
     */
    private function nullableBodyString(array $body, string $key): ?string
    {
        return isset($body[$key]) ? (string) $body[$key] : null;
    }

    /**
     * @param array<string, string|object|null> $body
     */
    private function fromConstructor(DomainEventEnvelope $envelope, array $body): DomainEvent
    {
        $eventClass = $envelope->eventClass();

        if (! is_a($eventClass, DomainEvent::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported domain event class "%s".',
                $eventClass
            ));
        }

        return (new ReflectionClass($eventClass))->newInstanceArgs([
            ...array_values($body),
            $envelope->eventId(),
            $envelope->occurredOn(),
        ]);
    }
}
