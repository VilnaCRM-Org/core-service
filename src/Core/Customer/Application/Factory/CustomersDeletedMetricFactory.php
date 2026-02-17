<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Metric\CustomersDeletedMetric;

/**
 * Factory for creating customer deletion metrics.
 *
 * Creates metrics with pure Value Objects - no service dependencies passed.
 */
final readonly class CustomersDeletedMetricFactory implements CustomersDeletedMetricFactoryInterface
{
    #[Override]
    public function create(float|int $value = 1): CustomersDeletedMetric
    {
        return new CustomersDeletedMetric($value);
    }
}
