<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\EndpointOperationMetricDimensions;
use App\Shared\Application\Observability\Metric\MetricDimensionsInterface;
use App\Shared\Application\Observability\Metric\MetricUnit;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactoryInterface;

/**
 * Test metric for OrdersPlaced
 */
final readonly class TestOrdersPlacedMetric extends BusinessMetric
{
    public function __construct(
        private MetricDimensionsFactoryInterface $dimensionsFactory,
        float|int $value = 1
    ) {
        parent::__construct($value, MetricUnit::COUNT);
    }

    public function name(): string
    {
        return 'OrdersPlaced';
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
