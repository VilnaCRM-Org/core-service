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
        $event = new CustomerDeletedEvent('customer-id', 'customer@example.com');

        self::assertSame('customer.deleted', $event->eventName());
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

        $event = new CustomerDeletedEvent($customerId, $customerEmail, $eventId, $occurredOn);

        self::assertSame($customerId, $event->customerId());
        self::assertSame($customerEmail, $event->customerEmail());
        self::assertSame($eventId, $event->eventId());
        self::assertSame($occurredOn, $event->occurredOn());
    }

    public function testGeneratedEventIdUsesPrefixedRandomHex(): void
    {
        $eventId = (new CustomerDeletedEvent(
            (string) $this->faker->ulid(),
            $this->faker->email()
        ))->eventId();

        self::assertStringStartsWith('customer_deleted_', $eventId);
        self::assertMatchesRegularExpression(
            '/^customer_deleted_[0-9a-f]{32}$/',
            $eventId
        );
    }

    public function testGeneratedEventIdsAreUnique(): void
    {
        $ids = [];
        for ($i = 0; $i < 1000; ++$i) {
            $ids[] = (new CustomerDeletedEvent(
                (string) $this->faker->ulid(),
                $this->faker->email()
            ))->eventId();
        }

        self::assertCount(1000, array_unique($ids));
    }
}
