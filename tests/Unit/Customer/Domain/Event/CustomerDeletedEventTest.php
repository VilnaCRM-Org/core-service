<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\Event;

use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Tests\Unit\UnitTestCase;

final class CustomerDeletedEventTest extends UnitTestCase
{
    public function testConstructorInitializesProperties(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = $this->faker->email();

        $event = new CustomerDeletedEvent($customerId, $customerEmail);

        self::assertSame($customerId, $event->customerId());
        self::assertSame($customerEmail, $event->customerEmail());
        self::assertNotEmpty($event->eventId());
        self::assertNotEmpty($event->occurredOn());
    }

    public function testConstructorAcceptsEventIdAndOccurredOn(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = $this->faker->email();
        $eventId = 'custom_event_id';
        $occurredOn = '2025-01-01 12:00:00';

        $event = new CustomerDeletedEvent(
            $customerId,
            $customerEmail,
            $eventId,
            $occurredOn
        );

        self::assertSame($eventId, $event->eventId());
        self::assertSame($occurredOn, $event->occurredOn());
    }

    public function testEventNameReturnsCorrectValue(): void
    {
        self::assertSame('customer.deleted', CustomerDeletedEvent::eventName());
    }

    public function testToPrimitivesReturnsCorrectArray(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = $this->faker->email();

        $event = new CustomerDeletedEvent($customerId, $customerEmail);
        $primitives = $event->toPrimitives();

        self::assertSame([
            'customer_id' => $customerId,
            'customer_email' => $customerEmail,
        ], $primitives);
    }

    public function testFromPrimitivesCreatesEventFromArray(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = $this->faker->email();
        $eventId = 'event_123';
        $occurredOn = '2025-01-01 12:00:00';

        $event = CustomerDeletedEvent::fromPrimitives(
            [
                'customer_id' => $customerId,
                'customer_email' => $customerEmail,
            ],
            $eventId,
            $occurredOn
        );

        self::assertSame($customerId, $event->customerId());
        self::assertSame($customerEmail, $event->customerEmail());
        self::assertSame($eventId, $event->eventId());
        self::assertSame($occurredOn, $event->occurredOn());
    }
}
