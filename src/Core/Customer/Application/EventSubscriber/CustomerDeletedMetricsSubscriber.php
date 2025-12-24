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
 * Error handling is provided by ResilientHandlerMiddleware.
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
    public function subscribedTo(): array
    {
        return [CustomerDeletedEvent::class];
    }
}
