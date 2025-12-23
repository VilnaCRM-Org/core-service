<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Metric\CustomersDeletedMetric;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactoryInterface;

final readonly class CustomersDeletedMetricFactory implements CustomersDeletedMetricFactoryInterface
{
    public function __construct(private MetricDimensionsFactoryInterface $dimensionsFactory)
    {
    }

    public function create(float|int $value = 1): CustomersDeletedMetric
    {
        return new CustomersDeletedMetric($this->dimensionsFactory, $value);
    }
}
