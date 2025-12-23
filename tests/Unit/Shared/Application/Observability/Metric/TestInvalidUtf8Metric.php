<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\MetricDimensionsInterface;
use App\Shared\Application\Observability\Metric\MetricUnit;

/**
 * Test metric that produces invalid UTF-8 for JSON encoding failure tests
 */
final readonly class TestInvalidUtf8Metric extends BusinessMetric
{
    public function __construct()
    {
        parent::__construct(1, MetricUnit::COUNT);
    }

    public function name(): string
    {
        return 'InvalidMetric';
    }

    public function dimensions(): MetricDimensionsInterface
    {
        return new TestInvalidUtf8Dimensions();
    }
}
