<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Metric\CustomersCreatedMetric;
use App\Shared\Application\Observability\Factory\MetricDimensionsFactoryInterface;

final readonly class CustomersCreatedMetricFactory implements CustomersCreatedMetricFactoryInterface
{
    public function __construct(private MetricDimensionsFactoryInterface $dimensionsFactory)
    {
    }

    public function create(float|int $value = 1): CustomersCreatedMetric
    {
        return new CustomersCreatedMetric($this->dimensionsFactory, $value);
    }
}
