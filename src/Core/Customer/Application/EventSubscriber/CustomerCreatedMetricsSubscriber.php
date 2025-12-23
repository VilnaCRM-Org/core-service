<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\Factory\CustomersCreatedMetricFactoryInterface;
use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Observability\Emitter\BusinessMetricsEmitterInterface;
use Psr\Log\LoggerInterface;

/**
 * Emits business metrics when a customer is created
 *
 * This subscriber listens to CustomerCreatedEvent and emits
 * the CustomersCreated metric for CloudWatch dashboards.
 */
final readonly class CustomerCreatedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private CustomersCreatedMetricFactoryInterface $metricFactory,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(CustomerCreatedEvent $event): void
    {
        try {
            $this->metricsEmitter->emit($this->metricFactory->create());

            $this->logger->debug('Business metric emitted', [
                'metric' => 'CustomersCreated',
                'event_id' => $event->eventId(),
            ]);
        } catch (\Throwable $e) {
            // Metrics emission is best-effort: don't fail the business operation
            $this->logger->warning('Failed to emit business metric', [
                'metric' => 'CustomersCreated',
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
        return [CustomerCreatedEvent::class];
    }
}
