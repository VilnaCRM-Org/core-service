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

        $this->subscriber = new CustomerDeletedMetricsSubscriber(
            $this->metricsEmitterSpy,
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

        $emitted = $this->metricsEmitterSpy->emitted();
        self::assertCount(1, $emitted);
        self::assertSame('CustomersDeleted', $emitted[0]['name']);
        self::assertSame(1, $emitted[0]['value']);
        self::assertSame('Customer', $emitted[0]['dimensions']['Endpoint']);
        self::assertSame('delete', $emitted[0]['dimensions']['Operation']);
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
                $this->callback(static function ($context) use ($customerId) {
                    return $context['metric'] === 'CustomersDeleted'
                        && $context['customer_id'] === $customerId
                        && isset($context['event_id']);
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

        $failingEmitter = $this->createMock(\App\Shared\Application\Observability\BusinessMetricsEmitterInterface::class);
        $failingEmitter
            ->method('emit')
            ->willThrowException(new \RuntimeException('Connection failed'));

        $subscriber = new CustomerDeletedMetricsSubscriber(
            $failingEmitter,
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
                    return $context['metric'] === 'CustomersDeleted'
                        && $context['customer_id'] === $customerId
                        && $context['error'] === 'Connection failed';
                })
            );

        // Should not throw exception - metrics are best-effort
        ($subscriber)($event);
    }
}
