<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\CacheRefreshMetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

final readonly class CacheRefreshStaleServedMetric extends BusinessMetric
{
    public function __construct(
        private string $context,
        private string $family,
        private string $source = 'read',
        float|int $value = 1
    ) {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    public function name(): string
    {
        return 'CacheRefreshStaleServed';
    }

    public function dimensions(): MetricDimensionsInterface
    {
        return new CacheRefreshMetricDimensions(
            $this->context,
            $this->family,
            $this->source,
            'stale_served'
        );
    }
}
