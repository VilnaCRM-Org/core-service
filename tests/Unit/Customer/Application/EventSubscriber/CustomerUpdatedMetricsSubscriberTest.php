<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\EventSubscriber\CustomerUpdatedMetricsSubscriber;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class CustomerUpdatedMetricsSubscriberTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsEmitterSpy;
    private LoggerInterface&MockObject $logger;
    private CustomerUpdatedMetricsSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsEmitterSpy = new BusinessMetricsEmitterSpy();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new CustomerUpdatedMetricsSubscriber(
            $this->metricsEmitterSpy,
            new \App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory(),
            $this->logger
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

    public function testInvokeLogsDebugMessageOnSuccess(): void
    {
        $customerId = (string) $this->faker->ulid();
        $currentEmail = 'test@example.com';

        $event = new CustomerUpdatedEvent(
            customerId: $customerId,
            currentEmail: $currentEmail
        );

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                'Business metric emitted',
                $this->callback(static function ($context) use ($customerId) {
                    return $context['metric'] === 'CustomersUpdated'
                        && $context['customer_id'] === $customerId
                        && isset($context['event_id']);
                })
            );

        ($this->subscriber)($event);
    }

    public function testInvokeLogsWarningOnEmitterFailure(): void
    {
        $customerId = (string) $this->faker->ulid();
        $currentEmail = 'test@example.com';

        $event = new CustomerUpdatedEvent(
            customerId: $customerId,
            currentEmail: $currentEmail
        );

        $failingEmitter = $this->createMock(\App\Shared\Application\Observability\BusinessMetricsEmitterInterface::class);
        $failingEmitter
            ->method('emit')
            ->willThrowException(new \RuntimeException('Connection failed'));

        $subscriber = new CustomerUpdatedMetricsSubscriber(
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
                $this->callback(static function ($context) use ($customerId) {
                    return $context['metric'] === 'CustomersUpdated'
                        && $context['customer_id'] === $customerId
                        && $context['error'] === 'Connection failed';
                })
            );

        // Should not throw exception - metrics are best-effort
        ($subscriber)($event);
    }
}
