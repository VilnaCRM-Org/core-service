<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Factory;

use App\Shared\Application\Observability\Metric\MetricDimension;
use App\Shared\Application\Observability\Metric\MetricDimensions;

interface MetricDimensionsFactoryInterface
{
    public function endpointOperation(string $endpoint, string $operation): MetricDimensions;

    public function endpointOperationWith(
        string $endpoint,
        string $operation,
        MetricDimension ...$extra
    ): MetricDimensions;
}
