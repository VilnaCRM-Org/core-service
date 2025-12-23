<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\Metric\CustomersDeletedMetric;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\MetricDimensionsFactoryInterface;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use Psr\Log\LoggerInterface;

/**
 * Emits business metrics when a customer is deleted
 *
 * This subscriber listens to CustomerDeletedEvent and emits
 * the CustomersDeleted metric for CloudWatch dashboards.
 */
final readonly class CustomerDeletedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private MetricDimensionsFactoryInterface $dimensionsFactory,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(CustomerDeletedEvent $event): void
    {
        try {
            $this->metricsEmitter->emit(new CustomersDeletedMetric($this->dimensionsFactory));

            $this->logger->debug('Business metric emitted', [
                'metric' => 'CustomersDeleted',
                'event_id' => $event->eventId(),
            ]);
        } catch (\Throwable $e) {
            // Metrics emission is best-effort: don't fail the business operation
            $this->logger->warning('Failed to emit business metric', [
                'metric' => 'CustomersDeleted',
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
        return [CustomerDeletedEvent::class];
    }
}
