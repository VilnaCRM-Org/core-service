<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactoryInterface;

final readonly class EndpointOperationMetricDimensions implements MetricDimensionsInterface
{
    public function __construct(
        private string $endpoint,
        private string $operation,
        private MetricDimensionsFactoryInterface $dimensionsFactory
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
        return $this->dimensionsFactory->endpointOperation($this->endpoint, $this->operation);
    }
}
