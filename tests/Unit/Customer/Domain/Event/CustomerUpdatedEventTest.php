<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\Event;

use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Tests\Unit\UnitTestCase;

final class CustomerUpdatedEventTest extends UnitTestCase
{
    public function testConstructorInitializesProperties(): void
    {
        $customerId = (string) $this->faker->ulid();
        $currentEmail = $this->faker->email();
        $previousEmail = $this->faker->email();

        $event = new CustomerUpdatedEvent(
            $customerId,
            $currentEmail,
            $previousEmail
        );

        self::assertSame($customerId, $event->customerId());
        self::assertSame($currentEmail, $event->currentEmail());
        self::assertSame($previousEmail, $event->previousEmail());
        self::assertNotEmpty($event->eventId());
        self::assertNotEmpty($event->occurredOn());
    }

    public function testConstructorWithNullPreviousEmail(): void
    {
        $customerId = (string) $this->faker->ulid();
        $currentEmail = $this->faker->email();

        $event = new CustomerUpdatedEvent($customerId, $currentEmail, null);

        self::assertNull($event->previousEmail());
    }

    public function testConstructorAcceptsEventIdAndOccurredOn(): void
    {
        $customerId = (string) $this->faker->ulid();
        $currentEmail = $this->faker->email();
        $eventId = 'custom_event_id';
        $occurredOn = '2025-01-01 12:00:00';

        $event = new CustomerUpdatedEvent(
            $customerId,
            $currentEmail,
            null,
            $eventId,
            $occurredOn
        );

        self::assertSame($eventId, $event->eventId());
        self::assertSame($occurredOn, $event->occurredOn());
    }

    public function testEventNameReturnsCorrectValue(): void
    {
        self::assertSame('customer.updated', CustomerUpdatedEvent::eventName());
    }

    public function testToPrimitivesReturnsCorrectArray(): void
    {
        $customerId = (string) $this->faker->ulid();
        $currentEmail = $this->faker->email();
        $previousEmail = $this->faker->email();

        $event = new CustomerUpdatedEvent(
            $customerId,
            $currentEmail,
            $previousEmail
        );
        $primitives = $event->toPrimitives();

        self::assertSame([
            'customer_id' => $customerId,
            'current_email' => $currentEmail,
            'previous_email' => $previousEmail,
        ], $primitives);
    }

    public function testToPrimitivesWithNullPreviousEmail(): void
    {
        $customerId = (string) $this->faker->ulid();
        $currentEmail = $this->faker->email();

        $event = new CustomerUpdatedEvent($customerId, $currentEmail, null);
        $primitives = $event->toPrimitives();

        self::assertSame([
            'customer_id' => $customerId,
            'current_email' => $currentEmail,
            'previous_email' => null,
        ], $primitives);
    }

    public function testFromPrimitivesCreatesEventFromArray(): void
    {
        $customerId = (string) $this->faker->ulid();
        $currentEmail = $this->faker->email();
        $previousEmail = $this->faker->email();
        $eventId = 'event_123';
        $occurredOn = '2025-01-01 12:00:00';

        $event = CustomerUpdatedEvent::fromPrimitives(
            [
                'customer_id' => $customerId,
                'current_email' => $currentEmail,
                'previous_email' => $previousEmail,
            ],
            $eventId,
            $occurredOn
        );

        self::assertSame($customerId, $event->customerId());
        self::assertSame($currentEmail, $event->currentEmail());
        self::assertSame($previousEmail, $event->previousEmail());
        self::assertSame($eventId, $event->eventId());
        self::assertSame($occurredOn, $event->occurredOn());
    }

    public function testFromPrimitivesWithNullPreviousEmail(): void
    {
        $customerId = (string) $this->faker->ulid();
        $currentEmail = $this->faker->email();
        $eventId = 'event_123';
        $occurredOn = '2025-01-01 12:00:00';

        $event = CustomerUpdatedEvent::fromPrimitives(
            [
                'customer_id' => $customerId,
                'current_email' => $currentEmail,
            ],
            $eventId,
            $occurredOn
        );

        self::assertNull($event->previousEmail());
    }

    public function testEmailChangedReturnsTrueWhenEmailsDiffer(): void
    {
        $customerId = (string) $this->faker->ulid();
        $currentEmail = 'new@example.com';
        $previousEmail = 'old@example.com';

        $event = new CustomerUpdatedEvent(
            $customerId,
            $currentEmail,
            $previousEmail
        );

        self::assertTrue($event->emailChanged());
    }

    public function testEmailChangedReturnsFalseWhenPreviousEmailIsNull(): void
    {
        $customerId = (string) $this->faker->ulid();
        $currentEmail = $this->faker->email();

        $event = new CustomerUpdatedEvent($customerId, $currentEmail, null);

        self::assertFalse($event->emailChanged());
    }

    public function testEmailChangedReturnsFalseWhenEmailsAreSame(): void
    {
        $customerId = (string) $this->faker->ulid();
        $email = 'same@example.com';

        $event = new CustomerUpdatedEvent($customerId, $email, $email);

        self::assertFalse($event->emailChanged());
    }
}
