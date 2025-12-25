<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\EventSubscriber\CustomerDeletedMetricsSubscriber;
use App\Core\Customer\Application\Factory\CustomersDeletedMetricFactory;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;

final class CustomerDeletedMetricsSubscriberTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsEmitterSpy;
    private CustomerDeletedMetricsSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsEmitterSpy = new BusinessMetricsEmitterSpy();

        $dimensionsFactory = new MetricDimensionsFactory();

        $this->subscriber = new CustomerDeletedMetricsSubscriber(
            $this->metricsEmitterSpy,
            new CustomersDeletedMetricFactory($dimensionsFactory)
        );
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertCount(1, $subscribedEvents);
        self::assertContains(CustomerDeletedEvent::class, $subscribedEvents);
    }

    public function testInvokeEmitsCustomersDeletedMetric(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'deleted@example.com';

        $event = new CustomerDeletedEvent(
            customerId: $customerId,
            customerEmail: $customerEmail
        );

        ($this->subscriber)($event);

        self::assertSame(1, $this->metricsEmitterSpy->count());

        foreach ($this->metricsEmitterSpy->emitted() as $metric) {
            self::assertSame('CustomersDeleted', $metric->name());
            self::assertSame(1, $metric->value());
            self::assertSame('Customer', $metric->dimensions()->values()->get('Endpoint'));
            self::assertSame('delete', $metric->dimensions()->values()->get('Operation'));
        }
    }

    public function testThrowsWhenEmitterFailsWithoutEventBusMiddleware(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test@example.com';

        $event = new CustomerDeletedEvent(
            customerId: $customerId,
            customerEmail: $customerEmail
        );

        $failingEmitter = $this->createMock(BusinessMetricsEmitterInterface::class);
        $failingEmitter
            ->method('emit')
            ->willThrowException(new \RuntimeException('Connection failed'));

        $dimensionsFactory = new MetricDimensionsFactory();

        $subscriber = new CustomerDeletedMetricsSubscriber(
            $failingEmitter,
            new CustomersDeletedMetricFactory($dimensionsFactory)
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection failed');

        ($subscriber)($event);
    }
}
