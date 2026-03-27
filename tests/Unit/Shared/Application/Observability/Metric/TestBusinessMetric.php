<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\Collection\MetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;

final readonly class TestBusinessMetric extends BusinessMetric
{
    public function name(): string
    {
        return 'TestMetric';
    }

    public function dimensions(): MetricDimensionsInterface
    {
        return new class() implements MetricDimensionsInterface {
            public function values(): MetricDimensions
            {
                return new MetricDimensions();
            }
        };
    }
}
