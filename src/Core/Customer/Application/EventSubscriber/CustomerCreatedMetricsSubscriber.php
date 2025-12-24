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
 * Error handling is provided by a resilient service decorator (non-critical subscriber).
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
    public function subscribedTo(): array
    {
        return [CustomerCreatedEvent::class];
    }
}
