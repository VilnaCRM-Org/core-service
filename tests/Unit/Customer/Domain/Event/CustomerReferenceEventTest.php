<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\Event;

use App\Core\Customer\Domain\Event\CustomerStatusCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerStatusUpdatedEvent;
use App\Core\Customer\Domain\Event\CustomerTypeCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerTypeUpdatedEvent;
use App\Tests\Unit\UnitTestCase;

final class CustomerReferenceEventTest extends UnitTestCase
{
    /**
     * @dataProvider createdEventProvider
     */
    public function testCreatedReferenceEventsSerializeAndRestore(
        string $eventClass,
        string $eventName,
        string $idKey,
        string $valueKey,
        string $idGetter,
        string $valueGetter
    ): void {
        $id = (string) $this->faker->ulid();
        $value = $this->faker->word();
        $eventId = 'event_123';
        $occurredOn = '2026-01-01T12:00:00+00:00';

        $event = new $eventClass($id, $value, $eventId, $occurredOn);

        self::assertSame($eventName, $eventClass::eventName());
        self::assertSame($id, $event->{$idGetter}());
        self::assertSame($value, $event->{$valueGetter}());
        self::assertSame($eventId, $event->eventId());
        self::assertSame($occurredOn, $event->occurredOn());
        self::assertSame([
            $idKey => $id,
            $valueKey => $value,
        ], $event->toPrimitives());

        $restored = $eventClass::fromPrimitives(
            [
                $idKey => $id,
                $valueKey => $value,
            ],
            'event_456',
            '2026-01-02T12:00:00+00:00'
        );

        self::assertSame($id, $restored->{$idGetter}());
        self::assertSame($value, $restored->{$valueGetter}());
        self::assertSame('event_456', $restored->eventId());
    }

    /**
     * @dataProvider updatedEventProvider
     */
    public function testUpdatedReferenceEventsSerializeAndRestore(
        string $eventClass,
        string $eventName,
        string $idKey,
        string $idGetter
    ): void {
        $id = (string) $this->faker->ulid();
        $currentValue = 'active';
        $previousValue = 'inactive';
        $eventId = 'event_123';
        $occurredOn = '2026-01-01T12:00:00+00:00';

        $event = new $eventClass(
            $id,
            $currentValue,
            $previousValue,
            $eventId,
            $occurredOn
        );

        self::assertSame($eventName, $eventClass::eventName());
        self::assertSame($id, $event->{$idGetter}());
        self::assertSame($currentValue, $event->currentValue());
        self::assertSame($previousValue, $event->previousValue());
        self::assertTrue($event->valueChanged());
        self::assertSame($eventId, $event->eventId());
        self::assertSame($occurredOn, $event->occurredOn());
        self::assertSame([
            $idKey => $id,
            'current_value' => $currentValue,
            'previous_value' => $previousValue,
        ], $event->toPrimitives());

        $restored = $eventClass::fromPrimitives(
            [
                $idKey => $id,
                'current_value' => $currentValue,
                'previous_value' => $previousValue,
            ],
            'event_456',
            '2026-01-02T12:00:00+00:00'
        );

        self::assertSame($id, $restored->{$idGetter}());
        self::assertSame($currentValue, $restored->currentValue());
        self::assertSame($previousValue, $restored->previousValue());
        self::assertSame('event_456', $restored->eventId());
    }

    /**
     * @dataProvider updatedEventProvider
     */
    public function testUpdatedReferenceEventsSupportNullPreviousValue(
        string $eventClass,
        string $eventName,
        string $idKey,
        string $idGetter
    ): void {
        $id = (string) $this->faker->ulid();
        $currentValue = 'active';

        $event = new $eventClass($id, $currentValue, null);

        self::assertSame($eventName, $eventClass::eventName());
        self::assertSame($id, $event->{$idGetter}());
        self::assertSame($currentValue, $event->currentValue());
        self::assertNull($event->previousValue());
        self::assertFalse($event->valueChanged());
        self::assertNotEmpty($event->eventId());
        self::assertNotEmpty($event->occurredOn());
        self::assertSame([
            $idKey => $id,
            'current_value' => $currentValue,
            'previous_value' => null,
        ], $event->toPrimitives());

        $restored = $eventClass::fromPrimitives(
            [
                $idKey => $id,
                'current_value' => $currentValue,
            ],
            'event_456',
            '2026-01-02T12:00:00+00:00'
        );

        self::assertNull($restored->previousValue());
    }

    /**
     * @return iterable<string, array{0: class-string, 1: string, 2: string, 3: string, 4: string, 5: string}>
     */
    public static function createdEventProvider(): iterable
    {
        yield 'status created' => [
            CustomerStatusCreatedEvent::class,
            'customer_status.created',
            'customer_status_id',
            'customer_status_value',
            'customerStatusId',
            'customerStatusValue',
        ];

        yield 'type created' => [
            CustomerTypeCreatedEvent::class,
            'customer_type.created',
            'customer_type_id',
            'customer_type_value',
            'customerTypeId',
            'customerTypeValue',
        ];
    }

    /**
     * @return iterable<string, array{0: class-string, 1: string, 2: string, 3: string}>
     */
    public static function updatedEventProvider(): iterable
    {
        yield 'status updated' => [
            CustomerStatusUpdatedEvent::class,
            'customer_status.updated',
            'customer_status_id',
            'customerStatusId',
        ];

        yield 'type updated' => [
            CustomerTypeUpdatedEvent::class,
            'customer_type.updated',
            'customer_type_id',
            'customerTypeId',
        ];
    }
}
