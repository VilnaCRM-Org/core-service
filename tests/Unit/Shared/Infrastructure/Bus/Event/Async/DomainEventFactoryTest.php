<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Core\Customer\Domain\Event\CustomerStatusCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerStatusUpdatedEvent;
use App\Core\Customer\Domain\Event\CustomerTypeCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerTypeUpdatedEvent;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Infrastructure\Bus\Event\Async\DomainEventEnvelope;
use App\Shared\Infrastructure\Bus\Event\Async\DomainEventFactory;
use App\Tests\Unit\UnitTestCase;
use InvalidArgumentException;
use stdClass;

final class DomainEventFactoryTest extends UnitTestCase
{
    private const EVENT_ID = 'event-123';
    private const OCCURRED_ON = '2024-01-15T10:30:00+00:00';

    private DomainEventFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DomainEventFactory();
    }

    public function testRestoresKnownCustomerEventsFromEnvelope(): void
    {
        foreach ($this->knownCustomerEventCases() as $case) {
            $event = $this->factory->fromEnvelope(new DomainEventEnvelope(
                eventClass: $case['class'],
                body: $case['body'],
                eventId: self::EVENT_ID,
                occurredOn: self::OCCURRED_ON
            ));

            self::assertInstanceOf($case['class'], $event);
            self::assertSame(self::EVENT_ID, $event->eventId());
            self::assertSame(self::OCCURRED_ON, $event->occurredOn());
            self::assertSame($case['expected'], $event->toPrimitives());
        }
    }

    public function testRejectsUnsupportedEventClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported domain event class "stdClass".');

        $this->factory->fromEnvelope(new DomainEventEnvelope(
            eventClass: stdClass::class,
            body: [],
            eventId: self::EVENT_ID,
            occurredOn: self::OCCURRED_ON
        ));
    }

    /**
     * @return list<array{
     *     class: class-string<DomainEvent>,
     *     body: array<string, string>,
     *     expected: array<string, string|null>
     * }>
     */
    private function knownCustomerEventCases(): array
    {
        return [
            [
                'class' => CustomerCreatedEvent::class,
                'body' => [
                    'customer_id' => 'customer-123',
                    'customer_email' => 'created@example.com',
                ],
                'expected' => [
                    'customer_id' => 'customer-123',
                    'customer_email' => 'created@example.com',
                ],
            ],
            [
                'class' => CustomerUpdatedEvent::class,
                'body' => [
                    'customer_id' => 'customer-123',
                    'current_email' => 'current@example.com',
                    'previous_email' => 'previous@example.com',
                ],
                'expected' => [
                    'customer_id' => 'customer-123',
                    'current_email' => 'current@example.com',
                    'previous_email' => 'previous@example.com',
                ],
            ],
            [
                'class' => CustomerDeletedEvent::class,
                'body' => [
                    'customer_id' => 'customer-123',
                    'customer_email' => 'deleted@example.com',
                ],
                'expected' => [
                    'customer_id' => 'customer-123',
                    'customer_email' => 'deleted@example.com',
                ],
            ],
            [
                'class' => CustomerStatusCreatedEvent::class,
                'body' => [
                    'customer_status_id' => 'status-123',
                    'customer_status_value' => 'Active',
                ],
                'expected' => [
                    'customer_status_id' => 'status-123',
                    'customer_status_value' => 'Active',
                ],
            ],
            [
                'class' => CustomerStatusUpdatedEvent::class,
                'body' => [
                    'customer_status_id' => 'status-123',
                    'current_value' => 'Active',
                    'previous_value' => 'Inactive',
                ],
                'expected' => [
                    'customer_status_id' => 'status-123',
                    'current_value' => 'Active',
                    'previous_value' => 'Inactive',
                ],
            ],
            [
                'class' => CustomerTypeCreatedEvent::class,
                'body' => [
                    'customer_type_id' => 'type-123',
                    'customer_type_value' => 'Lead',
                ],
                'expected' => [
                    'customer_type_id' => 'type-123',
                    'customer_type_value' => 'Lead',
                ],
            ],
            [
                'class' => CustomerTypeUpdatedEvent::class,
                'body' => [
                    'customer_type_id' => 'type-123',
                    'current_value' => 'Lead',
                    'previous_value' => 'Prospect',
                ],
                'expected' => [
                    'customer_type_id' => 'type-123',
                    'current_value' => 'Lead',
                    'previous_value' => 'Prospect',
                ],
            ],
        ];
    }
}
