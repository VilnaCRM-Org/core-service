<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\CacheRefreshMetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

final readonly class CacheHitMetric extends BusinessMetric
{
    public function __construct(
        private string $context,
        private string $family,
        float|int $value = 1
    ) {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    public function name(): string
    {
        return 'CacheHit';
    }

    public function dimensions(): MetricDimensionsInterface
    {
        return new CacheRefreshMetricDimensions($this->context, $this->family, 'read', 'hit');
    }
}
