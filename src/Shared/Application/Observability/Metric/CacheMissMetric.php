<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\CacheRefreshMetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

final readonly class CacheMissMetric extends BusinessMetric
{
    private function __construct(
        private string $context,
        private string $family,
        float|int $value
    ) {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    public static function create(string $context, string $family, float|int $value = 1): self
    {
        return new self($context, $family, $value);
    }

    public function name(): string
    {
        return 'CacheMiss';
    }

    public function dimensions(): MetricDimensionsInterface
    {
        return new CacheRefreshMetricDimensions($this->context, $this->family, 'read', 'miss');
    }
}
