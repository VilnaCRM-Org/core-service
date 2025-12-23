<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

final readonly class EndpointOperationMetricDimensions implements MetricDimensionsInterface
{
    public function __construct(
        private string $endpoint,
        private string $operation
    ) {
    }

    public function endpoint(): string
    {
        return $this->endpoint;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function values(): MetricDimensions
    {
        return MetricDimensions::endpointOperation($this->endpoint, $this->operation);
    }
}
