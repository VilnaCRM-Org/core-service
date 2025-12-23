<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\EventSubscriber\CustomerDeletedMetricsSubscriber;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class CustomerDeletedMetricsSubscriberTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsEmitterSpy;
    private LoggerInterface&MockObject $logger;
    private CustomerDeletedMetricsSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsEmitterSpy = new BusinessMetricsEmitterSpy();
        $this->logger = $this->createMock(LoggerInterface::class);

        $dimensionsFactory = new \App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory();

        $this->subscriber = new CustomerDeletedMetricsSubscriber(
            $this->metricsEmitterSpy,
            new \App\Core\Customer\Application\Factory\CustomersDeletedMetricFactory($dimensionsFactory),
            $this->logger
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

    public function testInvokeLogsDebugMessageOnSuccess(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test@example.com';

        $event = new CustomerDeletedEvent(
            customerId: $customerId,
            customerEmail: $customerEmail
        );

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                'Business metric emitted',
                $this->callback(static function ($context) {
                    return $context['metric'] === 'CustomersDeleted'
                        && isset($context['event_id'])
                        && ! isset($context['customer_id']); // PII should not be logged
                })
            );

        ($this->subscriber)($event);
    }

    public function testInvokeLogsWarningOnEmitterFailure(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test@example.com';

        $event = new CustomerDeletedEvent(
            customerId: $customerId,
            customerEmail: $customerEmail
        );

        $failingEmitter = $this->createMock(\App\Shared\Infrastructure\Observability\Emitter\BusinessMetricsEmitterInterface::class);
        $failingEmitter
            ->method('emit')
            ->willThrowException(new \RuntimeException('Connection failed'));

        $dimensionsFactory = new \App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory();

        $subscriber = new CustomerDeletedMetricsSubscriber(
            $failingEmitter,
            new \App\Core\Customer\Application\Factory\CustomersDeletedMetricFactory($dimensionsFactory),
            $this->logger
        );

        $this->logger
            ->expects($this->never())
            ->method('debug');

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Failed to emit business metric',
                $this->callback(static function ($context) {
                    return $context['metric'] === 'CustomersDeleted'
                        && isset($context['event_id'])
                        && $context['error'] === 'Connection failed'
                        && ! isset($context['customer_id']); // PII should not be logged
                })
            );

        // Should not throw exception - metrics are best-effort
        ($subscriber)($event);
    }
}
