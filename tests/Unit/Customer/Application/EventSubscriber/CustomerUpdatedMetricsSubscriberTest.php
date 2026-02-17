<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\EventSubscriber\CustomerUpdatedMetricsSubscriber;
use App\Core\Customer\Application\Factory\CustomersUpdatedMetricFactory;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;

final class CustomerUpdatedMetricsSubscriberTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsEmitterSpy;
    private CustomerUpdatedMetricsSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsEmitterSpy = new BusinessMetricsEmitterSpy();

        $dimensionsFactory = new MetricDimensionsFactory();

        $this->subscriber = new CustomerUpdatedMetricsSubscriber(
            $this->metricsEmitterSpy,
            new CustomersUpdatedMetricFactory($dimensionsFactory)
        );
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertCount(1, $subscribedEvents);
        self::assertContains(CustomerUpdatedEvent::class, $subscribedEvents);
    }

    public function testInvokeEmitsCustomersUpdatedMetric(): void
    {
        $customerId = (string) $this->faker->ulid();
        $currentEmail = 'new@example.com';

        $event = new CustomerUpdatedEvent(
            customerId: $customerId,
            currentEmail: $currentEmail
        );

        ($this->subscriber)($event);

        self::assertSame(1, $this->metricsEmitterSpy->count());

        foreach ($this->metricsEmitterSpy->emitted() as $metric) {
            self::assertSame('CustomersUpdated', $metric->name());
            self::assertSame(1, $metric->value());
            self::assertSame('Customer', $metric->dimensions()->values()->get('Endpoint'));
            self::assertSame('update', $metric->dimensions()->values()->get('Operation'));
        }
    }

    public function testThrowsWhenEmitterFailsWithoutEventBusMiddleware(): void
    {
        $customerId = (string) $this->faker->ulid();
        $currentEmail = 'test@example.com';

        $event = new CustomerUpdatedEvent(
            customerId: $customerId,
            currentEmail: $currentEmail
        );

        $failingEmitter = $this->createMock(BusinessMetricsEmitterInterface::class);
        $failingEmitter
            ->method('emit')
            ->willThrowException(new \RuntimeException('Connection failed'));

        $dimensionsFactory = new MetricDimensionsFactory();

        $subscriber = new CustomerUpdatedMetricsSubscriber(
            $failingEmitter,
            new CustomersUpdatedMetricFactory($dimensionsFactory)
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection failed');

        ($subscriber)($event);
    }
}
