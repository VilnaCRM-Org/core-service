<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\EndpointOperationMetricDimensions;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactoryInterface;

abstract readonly class EndpointOperationBusinessMetric extends BusinessMetric
{
    public function __construct(
        private MetricDimensionsFactoryInterface $dimensionsFactory,
        float|int $value,
        MetricUnit $unit
    ) {
        parent::__construct($value, $unit);
    }

    final public function dimensions(): MetricDimensionsInterface
    {
        return new EndpointOperationMetricDimensions(
            endpoint: $this->endpoint(),
            operation: $this->operation(),
            dimensionsFactory: $this->dimensionsFactory
        );
    }

    abstract protected function endpoint(): string;

    abstract protected function operation(): string;
}
