<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\Metric\CustomersUpdatedMetric;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\MetricDimensionsFactoryInterface;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use Psr\Log\LoggerInterface;

/**
 * Emits business metrics when a customer is updated
 *
 * This subscriber listens to CustomerUpdatedEvent and emits
 * the CustomersUpdated metric for CloudWatch dashboards.
 */
final readonly class CustomerUpdatedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private MetricDimensionsFactoryInterface $dimensionsFactory,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(CustomerUpdatedEvent $event): void
    {
        try {
            $this->metricsEmitter->emit(new CustomersUpdatedMetric($this->dimensionsFactory));

            $this->logger->debug('Business metric emitted', [
                'metric' => 'CustomersUpdated',
                'event_id' => $event->eventId(),
            ]);
        } catch (\Throwable $e) {
            // Metrics emission is best-effort: don't fail the business operation
            $this->logger->warning('Failed to emit business metric', [
                'metric' => 'CustomersUpdated',
                'event_id' => $event->eventId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [CustomerUpdatedEvent::class];
    }
}
