<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

/**
 * Base class for business metrics
 *
 * Each metric type should extend this class and provide
 * its own name, dimensions, and default unit.
 */
abstract readonly class BusinessMetric
{
    public function __construct(
        private float|int $value,
        private MetricUnit $unit
    ) {
    }

    abstract public function name(): string;

    abstract public function dimensions(): MetricDimensionsInterface;

    public function value(): float|int
    {
        if (! isset($this->value)) {
            return 0;
        }

        return $this->value;
    }

    public function unit(): MetricUnit
    {
        if (! isset($this->unit)) {
            return new MetricUnit(MetricUnit::COUNT);
        }

        return $this->unit;
    }
}
