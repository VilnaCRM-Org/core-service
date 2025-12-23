<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Factory;

use App\Shared\Application\Observability\Metric\MetricDimension;
use App\Shared\Application\Observability\Metric\MetricDimensions;

final class MetricDimensionsFactory implements MetricDimensionsFactoryInterface
{
    public function endpointOperation(string $endpoint, string $operation): MetricDimensions
    {
        return $this->endpointOperationWith($endpoint, $operation);
    }

    public function endpointOperationWith(
        string $endpoint,
        string $operation,
        MetricDimension ...$extra
    ): MetricDimensions {
        return new MetricDimensions(
            new MetricDimension('Endpoint', $endpoint),
            new MetricDimension('Operation', $operation),
            ...$extra
        );
    }
}
