<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

abstract readonly class EndpointOperationBusinessMetric extends BusinessMetric
{
    final public function dimensions(): MetricDimensionsInterface
    {
        return new EndpointOperationMetricDimensions(
            endpoint: $this->endpoint(),
            operation: $this->operation()
        );
    }

    abstract protected function endpoint(): string;

    abstract protected function operation(): string;
}
