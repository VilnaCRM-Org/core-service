<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Metric\CustomersUpdatedMetric;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactoryInterface;

final readonly class CustomersUpdatedMetricFactory implements CustomersUpdatedMetricFactoryInterface
{
    public function __construct(private MetricDimensionsFactoryInterface $dimensionsFactory)
    {
    }

    public function create(float|int $value = 1): CustomersUpdatedMetric
    {
        return new CustomersUpdatedMetric($this->dimensionsFactory, $value);
    }
}
