<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

interface MetricDimensionsInterface
{
    public function values(): MetricDimensions;
}
