<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Metric\CustomersUpdatedMetric;

/**
 * Factory for creating customer update metrics.
 *
 * Creates metrics with pure Value Objects - no service dependencies passed.
 */
final readonly class CustomersUpdatedMetricFactory implements CustomersUpdatedMetricFactoryInterface
{
    public function create(float|int $value = 1): CustomersUpdatedMetric
    {
        return new CustomersUpdatedMetric($value);
    }
}
