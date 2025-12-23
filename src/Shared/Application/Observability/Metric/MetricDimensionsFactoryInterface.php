<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

interface MetricDimensionsFactoryInterface
{
    public function endpointOperation(string $endpoint, string $operation): MetricDimensions;

    public function endpointOperationWith(
        string $endpoint,
        string $operation,
        MetricDimension ...$extra
    ): MetricDimensions;
}
