<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Subscriber;

use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\EndpointInvocationsMetric;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactoryInterface;
use App\Shared\Infrastructure\Observability\Resolver\ApiEndpointMetricDimensionsResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ApiEndpointBusinessMetricsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private ApiEndpointMetricDimensionsResolver $dimensionsResolver,
        private MetricDimensionsFactoryInterface $dimensionsFactory
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!str_starts_with($path, '/api')) {
            return;
        }

        $dimensions = $this->dimensionsResolver->dimensions($request);

        $this->metricsEmitter->emit(
            new EndpointInvocationsMetric(
                dimensionsFactory: $this->dimensionsFactory,
                endpoint: $dimensions->endpoint(),
                operation: $dimensions->operation()
            )
        );
    }
}
