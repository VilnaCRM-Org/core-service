<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\ValueObject\EndpointOperationMetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactoryInterface;

/**
 * Test metric for OrderValue
 */
final readonly class TestOrderValueMetric extends BusinessMetric
{
    public function __construct(
        private MetricDimensionsFactoryInterface $dimensionsFactory,
        float|int $value
    ) {
        parent::__construct($value, new MetricUnit(MetricUnit::NONE));
    }

    public function name(): string
    {
        return 'OrderValue';
    }

    public function dimensions(): MetricDimensionsInterface
    {
        return new EndpointOperationMetricDimensions(
            endpoint: 'Order',
            operation: 'create',
            dimensionsFactory: $this->dimensionsFactory
        );
    }
}
