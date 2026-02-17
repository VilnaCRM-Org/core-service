<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\Factory\CustomersCreatedMetricFactoryInterface;
use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

/**
 * Emits business metrics when a customer is created
 *
 * This subscriber listens to CustomerCreatedEvent and emits
 * the CustomersCreated metric for CloudWatch dashboards.
 *
 * ARCHITECTURAL DECISION: Processed via async queue (ResilientAsyncEventBus)
 * This subscriber runs in Symfony Messenger workers, wrapped with Layer 2 resilience.
 * DomainEventMessageHandler catches all failures, logs them, and emits metrics.
 * We follow AP from CAP theorem (Availability + Partition tolerance over Consistency).
 */
final readonly class CustomerCreatedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private CustomersCreatedMetricFactoryInterface $metricFactory
    ) {
    }

    public function __invoke(CustomerCreatedEvent $event): void
    {
        $this->metricsEmitter->emit($this->metricFactory->create());
    }

    /**
     * @return array<class-string>
     */
    #[Override]
    public function subscribedTo(): array
    {
        return [CustomerCreatedEvent::class];
    }
}
