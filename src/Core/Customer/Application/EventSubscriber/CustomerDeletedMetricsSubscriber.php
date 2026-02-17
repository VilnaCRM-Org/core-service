<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\Factory\CustomersDeletedMetricFactoryInterface;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

/**
 * Emits business metrics when a customer is deleted
 *
 * This subscriber listens to CustomerDeletedEvent and emits
 * the CustomersDeleted metric for CloudWatch dashboards.
 *
 * ARCHITECTURAL DECISION: Processed via async queue (ResilientAsyncEventBus)
 * This subscriber runs in Symfony Messenger workers, wrapped with Layer 2 resilience.
 * DomainEventMessageHandler catches all failures, logs them, and emits metrics.
 * We follow AP from CAP theorem (Availability + Partition tolerance over Consistency).
 */
final readonly class CustomerDeletedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private CustomersDeletedMetricFactoryInterface $metricFactory
    ) {
    }

    public function __invoke(CustomerDeletedEvent $event): void
    {
        $this->metricsEmitter->emit($this->metricFactory->create());
    }

    /**
     * @return array<class-string>
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [CustomerDeletedEvent::class];
    }
}
