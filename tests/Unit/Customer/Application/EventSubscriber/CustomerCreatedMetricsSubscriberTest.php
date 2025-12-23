<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\EventSubscriber\CustomerCreatedMetricsSubscriber;
use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class CustomerCreatedMetricsSubscriberTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsEmitterSpy;
    private LoggerInterface&MockObject $logger;
    private CustomerCreatedMetricsSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsEmitterSpy = new BusinessMetricsEmitterSpy();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new CustomerCreatedMetricsSubscriber(
            $this->metricsEmitterSpy,
            new \App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory(),
            $this->logger
        );
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertCount(1, $subscribedEvents);
        self::assertContains(CustomerCreatedEvent::class, $subscribedEvents);
    }

    public function testInvokeEmitsCustomersCreatedMetric(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test@example.com';

        $event = new CustomerCreatedEvent(
            customerId: $customerId,
            customerEmail: $customerEmail
        );

        ($this->subscriber)($event);

        self::assertSame(1, $this->metricsEmitterSpy->count());

        foreach ($this->metricsEmitterSpy->emitted() as $metric) {
            self::assertSame('CustomersCreated', $metric->name());
            self::assertSame(1, $metric->value());
            self::assertSame('Customer', $metric->dimensions()->values()->get('Endpoint'));
            self::assertSame('create', $metric->dimensions()->values()->get('Operation'));
        }
    }

    public function testInvokeLogsDebugMessageOnSuccess(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test@example.com';

        $event = new CustomerCreatedEvent(
            customerId: $customerId,
            customerEmail: $customerEmail
        );

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                'Business metric emitted',
                $this->callback(static function ($context) {
                    return $context['metric'] === 'CustomersCreated'
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

        $event = new CustomerCreatedEvent(
            customerId: $customerId,
            customerEmail: $customerEmail
        );

        $failingEmitter = $this->createMock(\App\Shared\Application\Observability\BusinessMetricsEmitterInterface::class);
        $failingEmitter
            ->method('emit')
            ->willThrowException(new \RuntimeException('Connection failed'));

        $subscriber = new CustomerCreatedMetricsSubscriber(
            $failingEmitter,
            new \App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory(),
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
                    return $context['metric'] === 'CustomersCreated'
                        && isset($context['event_id'])
                        && $context['error'] === 'Connection failed'
                        && ! isset($context['customer_id']); // PII should not be logged
                })
            );

        // Should not throw exception - metrics are best-effort
        ($subscriber)($event);
    }
}
