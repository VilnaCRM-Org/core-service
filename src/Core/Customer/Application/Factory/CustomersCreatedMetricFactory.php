<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Metric\CustomersCreatedMetric;

/**
 * Factory for creating customer creation metrics.
 *
 * Creates metrics with pure Value Objects - no service dependencies passed.
 */
final readonly class CustomersCreatedMetricFactory implements CustomersCreatedMetricFactoryInterface
{
    #[Override]
    public function create(float|int $value = 1): CustomersCreatedMetric
    {
        return new CustomersCreatedMetric($value);
    }
}
