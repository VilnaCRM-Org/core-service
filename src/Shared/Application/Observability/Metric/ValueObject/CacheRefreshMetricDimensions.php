<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric\ValueObject;

use App\Shared\Application\Observability\Metric\Collection\MetricDimensions;

final readonly class CacheRefreshMetricDimensions implements MetricDimensionsInterface
{
    public function __construct(
        private string $context,
        private string $family,
        private string $source,
        private string $result
    ) {
    }

    public function values(): MetricDimensions
    {
        return new MetricDimensions(
            new MetricDimension('Context', $this->context),
            new MetricDimension('Family', $this->family),
            new MetricDimension('Source', $this->source),
            new MetricDimension('Result', $this->result)
        );
    }
}
