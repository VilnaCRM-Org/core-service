<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\EventSubscriber\CustomerCreatedMetricsSubscriber;
use App\Core\Customer\Application\Factory\CustomersCreatedMetricFactory;
use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
use App\Shared\Infrastructure\Bus\Middleware\ResilientHandlerMiddleware;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\LoggerInterface;

final class CustomerCreatedMetricsSubscriberTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsEmitterSpy;
    private CustomerCreatedMetricsSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsEmitterSpy = new BusinessMetricsEmitterSpy();

        $dimensionsFactory = new MetricDimensionsFactory();

        $this->subscriber = new CustomerCreatedMetricsSubscriber(
            $this->metricsEmitterSpy,
            new CustomersCreatedMetricFactory($dimensionsFactory)
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

    public function testDoesNotThrowWhenEmitterFailsThroughEventBus(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test@example.com';

        $event = new CustomerCreatedEvent(
            customerId: $customerId,
            customerEmail: $customerEmail
        );

        $failingEmitter = $this->createMock(BusinessMetricsEmitterInterface::class);
        $failingEmitter
            ->method('emit')
            ->willThrowException(new \RuntimeException('Connection failed'));

        $dimensionsFactory = new MetricDimensionsFactory();
        $subscriber = new CustomerCreatedMetricsSubscriber(
            $failingEmitter,
            new CustomersCreatedMetricFactory($dimensionsFactory)
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Event subscriber execution failed',
                self::callback(static function (array $context): bool {
                    return ($context['message_class'] ?? null) === CustomerCreatedEvent::class
                        && isset($context['error'])
                        && str_contains((string) $context['error'], 'Connection failed')
                        && ($context['exception_class'] ?? null) === \Symfony\Component\Messenger\Exception\HandlerFailedException::class;
                })
            );

        $bus = (new MessageBusFactory([
            new ResilientHandlerMiddleware($logger),
        ]))->create([$subscriber]);

        $bus->dispatch($event);

        self::assertTrue(true);
    }
}
